<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CustomerLedger;
use App\Models\BankAccount;
use App\Models\Sales\SalesInvoice;
use App\Models\Treasury\Treasury;
use App\Models\Treasury\TreasuryTransaction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Collection::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('collection_no', 'like', "%$s%");
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('collection', 'store'));

        return DB::transaction(function () use ($data, $request) {
            $collection = Collection::create($data);

            if ($collection->status === 'approved' && !empty($data['sales_invoice_id'])) {
                $invoice = SalesInvoice::find($data['sales_invoice_id']);
                if ($invoice) {
                    $paidAmount = (float) ($invoice->paid_amount ?? 0) + (float) $collection->amount;
                    $remaining = (float) $invoice->net_total - $paidAmount;
                    if ($remaining < 0) $remaining = 0;
                    $invoice->update([
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $remaining,
                    ]);

                    $customerBalance = CustomerLedger::where('customer_id', $collection->customer_id)
                        ->orderByDesc('id')->value('balance') ?? 0;
                    $newBalance = (float) $customerBalance - (float) $collection->amount;

                    CustomerLedger::create([
                        'customer_id' => $collection->customer_id,
                        'transaction_date' => $collection->collection_date ?? now(),
                        'reference_type' => 'App\\Models\\Collection',
                        'reference_id' => $collection->id,
                        'debit' => 0,
                        'credit' => $collection->amount,
                        'balance' => $newBalance,
                    ]);
                }
            }

            if ($collection->status === 'approved' && !empty($data['safe_id'])) {
                $treasury = Treasury::find($data['safe_id']);
                if ($treasury) {
                    TreasuryTransaction::create([
                        'company_id' => $collection->company_id,
                        'treasury_id' => $data['safe_id'],
                        'type' => 'credit',
                        'amount' => $collection->amount,
                        'reference_type' => 'App\\Models\\Collection',
                        'reference_id' => $collection->id,
                        'description' => 'سند سداد ' . $collection->collection_no,
                        'transaction_date' => $collection->collection_date ?? now(),
                        'created_by' => $collection->created_by,
                    ]);
                }
            }

            if ($collection->status === 'approved' && !empty($data['bank_account_id'])) {
                $bankAccount = BankAccount::find($data['bank_account_id']);
                if ($bankAccount) {
                    $currentBalance = (float) ($bankAccount->current_balance ?? 0);
                    $bankAccount->update([
                        'current_balance' => $currentBalance + (float) $collection->amount,
                    ]);
                }
            }

            return response()->json($collection, 201);
        });
    }

    public function show(Collection $collection)
    {
        return $collection->load(['company', 'branch', 'salesRep', 'customer', 'salesInvoice', 'paymentMethod']);
    }

    public function update(Request $request, Collection $collection)
    {
        $data = $request->validate(ValidationRules::for('collection', 'update', $collection));
        $collection->update($data);
        return response()->json($collection);
    }

    public function destroy(Collection $collection)
    {
        DB::transaction(function () use ($collection) {
            if ($collection->status === 'approved' && $collection->sales_invoice_id) {
                $invoice = SalesInvoice::find($collection->sales_invoice_id);
                if ($invoice) {
                    $paidAmount = (float) ($invoice->paid_amount ?? 0) - (float) $collection->amount;
                    if ($paidAmount < 0) $paidAmount = 0;
                    $remaining = (float) $invoice->net_total - $paidAmount;
                    $invoice->update([
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $remaining,
                    ]);

                    $ledgerEntry = CustomerLedger::where('reference_type', 'App\\Models\\Collection')
                        ->where('reference_id', $collection->id)
                        ->first();
                    if ($ledgerEntry) {
                        $ledgerEntry->delete();
                    }
                }
            }

            if ($collection->status === 'approved' && !empty($collection->safe_id)) {
                TreasuryTransaction::where('reference_type', 'App\\Models\\Collection')
                    ->where('reference_id', $collection->id)
                    ->delete();
            }

            if ($collection->status === 'approved' && !empty($collection->bank_account_id)) {
                $bankAccount = BankAccount::find($collection->bank_account_id);
                if ($bankAccount) {
                    $currentBalance = (float) ($bankAccount->current_balance ?? 0);
                    $bankAccount->update([
                        'current_balance' => $currentBalance - (float) $collection->amount,
                    ]);
                }
            }

            $collection->delete();
        });

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = Collection::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        Collection::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('collection', 'store');
    }
}
