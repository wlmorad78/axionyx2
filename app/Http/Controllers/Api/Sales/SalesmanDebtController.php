<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesmanDebt;
use App\Models\Sales\SalesmanDebtPaymentLine;
use App\Models\Sales\Collection;
use App\Models\Treasury\TreasuryTransaction;
use App\Models\Treasury\Treasury;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesmanDebtController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        if (!in_array('salesman', $with)) {
            $with[] = 'salesman';
        }
        $query = SalesmanDebt::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->debt_date_from) {
            $query->whereDate('debt_date', '>=', $request->debt_date_from);
        }

        if ($request->debt_date_to) {
            $query->whereDate('debt_date', '<=', $request->debt_date_to);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('debt_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function show(SalesmanDebt $salesmanDebt)
    {
        return $salesmanDebt->load([
            'company', 'branch', 'salesman', 'salesmanAccount',
            'returnAuthorization', 'salesmanAssignment',
            'paymentLines', 'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    public function collect(Request $request, SalesmanDebt $salesmanDebt)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'payment_type' => ['sometimes', 'in:cash,bank,check'],
            'notes' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) $data['amount'];

        if ($paidAmount <= 0) {
            return response()->json(['message' => 'Ø§Ù„Ù…Ø¨Ù„Øº ÙŠØ¬Ø¨ Ø£Ù† ÙŠÙƒÙˆÙ† Ø£ÙƒØ¨Ø± Ù…Ù† ØµÙØ±'], 422);
        }

        if ($paidAmount > $salesmanDebt->remaining_debt) {
            return response()->json([
                'message' => 'Ø§Ù„Ù…Ø¨Ù„Øº Ø§Ù„Ù…Ø¯ÙÙˆØ¹ ÙŠØªØ¬Ø§ÙˆØ² Ø§Ù„Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø§Ù„Ù…ØªØ¨Ù‚ÙŠØ©',
                'remaining_debt' => $salesmanDebt->remaining_debt,
            ], 422);
        }

        $user = $request->user();
        $employee = \App\Models\HR\Employee::where('email', $user->email)->first();

        return DB::transaction(function () use ($request, $salesmanDebt, $data, $paidAmount, $employee) {
            // Determine the treasury
            $treasuryId = $data['treasury_id'] ?? null;
            if (!$treasuryId) {
                $treasury = Treasury::where('company_id', $salesmanDebt->company_id)
                    ->where('is_main', true)->where('is_active', true)->first();
                if ($treasury) {
                    $treasuryId = $treasury->id;
                }
            }

            // Create the payment line
            $remainingAfterPayment = $salesmanDebt->remaining_debt - $paidAmount;

            $paymentLine = SalesmanDebtPaymentLine::create([
                'company_id' => $salesmanDebt->company_id,
                'branch_id' => $salesmanDebt->branch_id,
                'salesman_debt_id' => $salesmanDebt->id,
                'salesman_account_id' => $salesmanDebt->salesman_account_id,
                'salesman_id' => $salesmanDebt->salesman_id,
                'payment_date' => $data['payment_date'],
                'amount' => $paidAmount,
                'remaining_after_payment' => $remainingAfterPayment,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'treasury_id' => $treasuryId,
                'reference_no' => $data['reference_no'] ?? null,
                'payment_type' => $data['payment_type'] ?? 'cash',
                'notes' => $data['notes'] ?? 'ØªØ­ØµÙŠÙ„ Ù‚Ø³Ø· Ù…Ù† Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© ' . $salesmanDebt->debt_no,
                'received_by' => $employee?->id,
                'created_by' => $employee?->id,
            ]);

            // Update the debt
            $isFullyPaid = $remainingAfterPayment <= 0;
            $salesmanDebt->update([
                'total_paid' => $salesmanDebt->total_paid + $paidAmount,
                'remaining_debt' => $remainingAfterPayment,
                'status' => $isFullyPaid ? 'fully_paid' : 'partially_paid',
            ]);

            // Update linked settlement shortage_status
            if ($isFullyPaid) {
                \App\Models\Sales\RepDailySettlement::where('salesman_debt_id', $salesmanDebt->id)
                    ->update(['shortage_status' => 'paid_next_day']);
            }

            // Create treasury transaction
            if ($treasuryId) {
                TreasuryTransaction::create([
                    'company_id' => $salesmanDebt->company_id,
                    'treasury_id' => $treasuryId,
                    'type' => 'credit',
                    'amount' => $paidAmount,
                    'reference_type' => SalesmanDebtPaymentLine::class,
                    'reference_id' => $paymentLine->id,
                    'description' => 'تحصيل من مديونية المندوب: ' . ($salesmanDebt->salesman?->getFullNameArAttribute() ?? $salesmanDebt->debt_no),
                    'transaction_date' => $data['payment_date'],
                    'created_by' => $employee?->id,
                ]);
            }

            // Create collection record
            $collection = Collection::create([
                'company_id' => $salesmanDebt->company_id,
                'branch_id' => $salesmanDebt->branch_id,
                'collection_no' => Collection::generateCollectionNoForDebt($salesmanDebt),
                'collection_date' => $data['payment_date'],
                'collection_time' => now()->format('H:i:s'),
                'sales_rep_id' => $salesmanDebt->salesman_id,
                'amount' => $paidAmount,
                'reference_no' => $data['reference_no'] ?? null,
                'collection_type' => 'salesman_debt_collection',
                'debt_id' => $salesmanDebt->id,
                'debt_payment_line_id' => $paymentLine->id,
                'collected_from_id' => $employee?->id,
                'notes' => $data['notes'] ?? null,
                'status' => 'approved',
                'approved_by' => $employee?->id,
                'created_by' => $employee?->id,
            ]);

            $paymentLine->update(['collection_id' => $collection->id]);

            // Update the debt
            if ($salesmanDebt->salesman_account_id) {
                $account = \App\Models\Sales\SalesmanAccount::find($salesmanDebt->salesman_account_id);
                if ($account) {
                    $account->update([
                        'total_collections' => $account->total_collections + $paidAmount,
                        'current_balance' => $account->current_balance - $paidAmount,
                    ]);

                    \App\Models\Sales\SalesmanAccountMovement::create([
                        'company_id' => $salesmanDebt->company_id,
                        'branch_id' => $salesmanDebt->branch_id,
                        'salesman_account_id' => $account->id,
                        'salesman_id' => $salesmanDebt->salesman_id,
                        'movement_date' => $data['payment_date'],
                        'movement_type' => 'collection',
                        'reference_type' => SalesmanDebtPaymentLine::class,
                        'reference_id' => $paymentLine->id,
                        'document_no' => $salesmanDebt->debt_no,
                        'credit' => $paidAmount,
                        'balance' => $account->current_balance - $paidAmount,
                        'description' => 'ØªØ­ØµÙŠÙ„ Ù…Ù† Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨: ' . $salesmanDebt->debt_no,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => $employee?->id,
                    ]);
                }
            }

            return response()->json([
                'message' => $isFullyPaid ? 'ØªÙ… Ø³Ø¯Ø§Ø¯ Ø§Ù„Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø¨Ø§Ù„ÙƒØ§Ù…Ù„' : 'ØªÙ… ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯ÙØ¹Ø© Ø¨Ù†Ø¬Ø§Ø­',
                'payment_line' => $paymentLine->load('treasury'),
                'salesman_debt' => $salesmanDebt->fresh(),
            ], 201);
        });
    }

    public function update(SalesmanDebt $salesmanDebt, Request $request)
    {
        $data = $request->validate([
            'due_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:20'],
        ]);

        $salesmanDebt->update($data);
        return response()->json($salesmanDebt->fresh());
    }

    public function destroy(SalesmanDebt $salesmanDebt)
    {
        $salesmanDebt->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesmanDebt::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesmanDebt::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('salesman_debt', 'store');
    }
}