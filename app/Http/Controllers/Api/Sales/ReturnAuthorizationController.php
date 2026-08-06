<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\ReturnAuthorization;
use App\Models\Sales\ReturnAuthorizationItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryTransactionItem;
use App\Models\Inventory\InventoryTransactionType;
use App\Models\Sales\SalesmanAccount;
use App\Models\Sales\SalesmanDebt;
use App\Models\Sales\SalesmanAccountMovement;
use App\Models\HR\Employee;
use App\Models\Inventory\Item;
use App\Models\Unit;
use App\Models\Sales\SalesInvoice;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnAuthorizationController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ReturnAuthorization::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->return_date_from) {
            $query->whereDate('return_date', '>=', $request->return_date_from);
        }

        if ($request->return_date_to) {
            $query->whereDate('return_date', '<=', $request->return_date_to);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('return_auth_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('return_authorization', 'store'));

        return DB::transaction(function () use ($request, $data) {
            $returnAuth = ReturnAuthorization::create($data);

            if (!empty($data['items'] ?? null)) {
                foreach ($data['items'] as $itemData) {
                    $itemData['return_authorization_id'] = $returnAuth->id;
                    ReturnAuthorizationItem::create($itemData);
                }
                $returnAuth->recalculateTotals();
            }

            return response()->json($returnAuth->load(['items.item', 'items.unit', 'salesman', 'customer', 'warehouse']), 201);
        });
    }

    public function show(ReturnAuthorization $returnAuthorization)
    {
        return $returnAuthorization->load([
            'company', 'branch', 'warehouse', 'salesman', 'salesmanAccount', 'customer', 'salesRoute',
            'items.item', 'items.unit', 'items.salesInvoice', 'items.salesInvoiceItem',
            'createdByEmployee', 'approvedByEmployee', 'salesmanDebt',
        ]);
    }

    public function update(Request $request, ReturnAuthorization $returnAuthorization)
    {
        $data = $request->validate(ValidationRules::for('return_authorization', 'update', $returnAuthorization));

        return DB::transaction(function () use ($request, $returnAuthorization, $data) {
            if (!empty($data['items'] ?? null)) {
                $returnAuthorization->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $itemData['return_authorization_id'] = $returnAuthorization->id;
                    ReturnAuthorizationItem::create($itemData);
                }
                unset($data['items']);
            }

            $returnAuthorization->update($data);
            $returnAuthorization->recalculateTotals();

            return response()->json($returnAuthorization->fresh()->load('items.item', 'items.unit'));
        });
    }

    public function destroy(ReturnAuthorization $returnAuthorization)
    {
        $returnAuthorization->delete();
        return response()->json(null, 204);
    }

    public function approve(Request $request, ReturnAuthorization $returnAuthorization)
    {
        if ($returnAuthorization->status !== 'draft' && $returnAuthorization->status !== 'pending') {
            return response()->json([
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø§Ø¹ØªÙ…Ø§Ø¯ Ø¥Ø°Ù† Ø§Ø±ØªØ¬Ø§Ø¹ Ø¨Ø­Ø§Ù„Ø©: ' . $returnAuthorization->status,
            ], 422);
        }

        $user = $request->user();
        $employee = Employee::where('email', $user->email)->first();

        return DB::transaction(function () use ($returnAuthorization, $request, $employee, $user) {
            $returnAuthorization->load('items.item', 'items.unit', 'salesman', 'customer', 'warehouse');

            $acceptedItems = $returnAuthorization->items->where('acceptance_status', 'accepted');
            $totalReturnValue = $acceptedItems->sum('net_amount');

            $returnAuthorization->update([
                'total_return_value' => $totalReturnValue,
                'net_debt_amount' => bcsub((string)$returnAuthorization->total_sales_value, (string)$totalReturnValue, 2),
                'status' => 'approved',
                'approved_by' => $employee?->id,
                'approved_at' => now(),
                'notes' => $request->notes ?? 'Ø¥Ø¹ØªÙ…Ø§Ø¯ Ø§Ø¹ØªÙ…Ø§Ø¯ Ø§Ø±ØªØ¬Ø§Ø¹ Ø±Ù‚Ù…: ' . $returnAuthorization->return_auth_no,
            ]);

            // === Ø­Ø±ÙƒØ© Ø§Ù„Ù…Ø®Ø²ÙˆÙ† ===
            $txnType = InventoryTransactionType::firstOrCreate(
                ['code' => 'SALESMAN_RETURN'],
                ['name' => 'Ø¹ÙˆØ¯Ø© Ø¨Ø¶Ø§Ø¹Ø© Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨', 'effect' => 'addition', 'is_active' => true]
            );

            $txn = InventoryTransaction::create([
                'company_id' => $returnAuthorization->company_id,
                'branch_id' => $returnAuthorization->branch_id,
                'warehouse_id' => $returnAuthorization->warehouse_id,
                'transaction_type_id' => $txnType->id,
                'transaction_no' => InventoryTransaction::nextTransactionNo($returnAuthorization->company_id),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => ReturnAuthorization::class,
                'reference_id' => $returnAuthorization->id,
                'notes' => 'Ø¹ÙˆØ¯Ø© Ø¨Ø¶Ø§Ø¹Ø© Ù…Ù† Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨: ' . $returnAuthorization->salesman?->getFullNameAttribute() . ' - ' . $returnAuthorization->return_auth_no,
                'status' => 'posted',
                'created_by' => $employee?->id,
            ]);

            foreach ($acceptedItems as $authItem) {
                $unitId = $authItem->unit_id ?? $authItem->item?->base_unit_id;
                $qtyInBase = $authItem->qty;

                InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $authItem->item_id,
                    'unit_id' => $unitId,
                    'qty' => $qtyInBase,
                    'unit_cost' => $authItem->price,
                    'total_cost' => $authItem->net_amount,
                    'from_location_type' => 'salesman',
                    'from_location_id' => $returnAuthorization->salesman_id,
                    'to_location_type' => 'warehouse',
                    'to_location_id' => $returnAuthorization->warehouse_id,
                ]);

                // ØªØ­Ø¯ÙŠØ« rep_item_distributions Ø¥Ø°Ø§ ÙˆÙØ¬Ø¯
                $this->updateRepItemDistribution($authItem);
            }

            // === Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨ ===
            $salesmanAccount = $returnAuthorization->salesmanAccount ?? SalesmanAccount::where('salesman_id', $returnAuthorization->salesman_id)->where('company_id', $returnAuthorization->company_id)->first();

            if (!$salesmanAccount && $returnAuthorization->salesman_id) {
                $salesmanAccount = SalesmanAccount::create([
                    'company_id' => $returnAuthorization->company_id,
                    'branch_id' => $returnAuthorization->branch_id,
                    'salesman_id' => $returnAuthorization->salesman_id,
                    'opening_date' => now()->toDateString(),
                    'opening_balance' => 0,
                    'current_balance' => 0,
                ]);
                $returnAuthorization->update(['salesman_account_id' => $salesmanAccount->id]);
            }

            // Ø¥Ù†Ø´Ø§Ø¡ Ø­Ø±ÙƒØ© Ø­Ø³Ø§Ø¨ Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨ - ØªØ­Ù…ÙŠÙ„ Ø§Ù„Ø¨Ø¶Ø§Ø¹Ø© Ø§Ø­ØªØ³Ø§Ø¨Ø§Ù‹ Ù„Ù„Ù…Ø¨ÙŠØ¹Ø§Øª
            if ($returnAuthorization->total_sales_value > 0) {
                SalesmanAccountMovement::create([
                    'company_id' => $returnAuthorization->company_id,
                    'branch_id' => $returnAuthorization->branch_id,
                    'salesman_account_id' => $salesmanAccount->id,
                    'salesman_id' => $returnAuthorization->salesman_id,
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'return_approved',
                    'reference_type' => ReturnAuthorization::class,
                    'reference_id' => $returnAuthorization->id,
                    'document_no' => $returnAuthorization->return_auth_no,
                    'credit' => $totalReturnValue,
                    'balance' => $salesmanAccount->current_balance - $totalReturnValue,
                    'description' => 'Ø®ØµÙ… Ù…Ø±ØªØ¬Ø¹Ø§Øª Ù…Ù‚Ø¨ÙˆÙ„Ø© Ù…Ù† Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨',
                    'notes' => 'Ø¥Ø°Ù† Ø§Ø±ØªØ¬Ø§Ø¹: ' . $returnAuthorization->return_auth_no,
                    'created_by' => $employee?->id,
                ]);
                $salesmanAccount->update([
                    'total_returns' => $salesmanAccount->total_returns + $totalReturnValue,
                    'current_balance' => $salesmanAccount->current_balance - $totalReturnValue,
                ]);
            }

            // Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨
            $salesmanDebt = SalesmanDebt::create([
                'company_id' => $returnAuthorization->company_id,
                'branch_id' => $returnAuthorization->branch_id,
                'salesman_id' => $returnAuthorization->salesman_id,
                'salesman_account_id' => $salesmanAccount->id,
                'return_authorization_id' => $returnAuthorization->id,
                'debt_date' => now()->toDateString(),
                'total_sales' => $returnAuthorization->total_sales_value,
                'total_returns' => $totalReturnValue,
                'gross_debt' => $returnAuthorization->net_debt_amount,
                'remaining_debt' => $returnAuthorization->net_debt_amount,
                'status' => $returnAuthorization->net_debt_amount > 0 ? 'pending' : 'fully_paid',
                'notes' => 'Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ù†Ø§ØªØ¬Ø© Ø¹Ù† Ø¥Ø°Ù† Ø§Ø±ØªØ¬Ø§Ø¹: ' . $returnAuthorization->return_auth_no,
                'created_by' => $employee?->id,
            ]);

            $returnAuthorization->update(['salesman_debt_id' => $salesmanDebt->id]);

            // ØªØ³Ø¬ÙŠÙ„ ÙÙŠ ÙƒØ´Ù Ø­Ø³Ø§Ø¨ Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨
            if ($returnAuthorization->net_debt_amount > 0) {
                SalesmanAccountMovement::create([
                    'company_id' => $returnAuthorization->company_id,
                    'branch_id' => $returnAuthorization->branch_id,
                    'salesman_account_id' => $salesmanAccount->id,
                    'salesman_id' => $returnAuthorization->salesman_id,
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'debt_creation',
                    'reference_type' => SalesmanDebt::class,
                    'reference_id' => $salesmanDebt->id,
                    'document_no' => $salesmanDebt->debt_no,
                    'debit' => $returnAuthorization->net_debt_amount,
                    'balance' => $salesmanAccount->current_balance + $returnAuthorization->net_debt_amount,
                    'description' => 'Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø¬Ø¯ÙŠØ¯Ø© Ù„Ù„Ù…Ù†Ø¯ÙˆØ¨',
                    'notes' => 'Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ù…Ø¨ÙŠØ¹Ø§Øª: ' . $returnAuthorization->total_sales_value . ' - Ø§Ù„Ù…Ø±ØªØ¬Ø¹Ø§Øª: ' . $totalReturnValue,
                    'created_by' => $employee?->id,
                ]);
                $salesmanAccount->update([
                    'current_balance' => $salesmanAccount->current_balance + $returnAuthorization->net_debt_amount,
                    'total_debts' => $salesmanAccount->total_debts + $returnAuthorization->net_debt_amount,
                ]);
            }

            return response()->json([
                'message' => 'ØªÙ… Ø§Ø¹ØªÙ…Ø§Ø¯ Ø¥Ø°Ù† Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹ ÙˆØ¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ø¨Ù†Ø¬Ø§Ø­',
                'return_authorization' => $returnAuthorization->fresh()->load('items.item', 'items.unit', 'salesmanDebt'),
                'salesman_debt' => $salesmanDebt,
            ]);
        });
    }

    public function reject(Request $request, ReturnAuthorization $returnAuthorization)
    {
        if ($returnAuthorization->status !== 'draft' && $returnAuthorization->status !== 'pending') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø±ÙØ¶ Ø¥Ø°Ù† Ø§Ø±ØªØ¬Ø§Ø¹ Ø¨Ø­Ø§Ù„Ø©: ' . $returnAuthorization->status], 422);
        }

        $returnAuthorization->update([
            'status' => 'rejected',
            'notes' => $request->notes ?? 'ØªÙ… Ø±ÙØ¶ Ø¥Ø°Ù† Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹',
        ]);

        return response()->json([
            'message' => 'ØªÙ… Ø±ÙØ¶ Ø¥Ø°Ù† Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹',
            'return_authorization' => $returnAuthorization->fresh(),
        ]);
    }

    public function restore(int $id)
    {
        $model = ReturnAuthorization::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ReturnAuthorization::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('return_authorization', 'store');
    }

    private function updateRepItemDistribution(ReturnAuthorizationItem $authItem): void
    {
        if (!$authItem->sales_invoice_item_id) return;

        $invoiceItem = \App\Models\Sales\SalesInvoiceItem::find($authItem->sales_invoice_item_id);
        if (!$invoiceItem) return;

        $distribution = \App\Models\Sales\RepItemDistribution::where('item_id', $authItem->item_id)
            ->where('employee_id', $authItem->returnAuthorization->salesman_id)
            ->where('status', 'active')
            ->first();

        if ($distribution) {
            $newReturnedQty = $distribution->returned_qty + $authItem->qty;
            $newRemainingQty = $distribution->remaining_qty - $authItem->qty;

            $distribution->update([
                'returned_qty' => $newReturnedQty,
                'remaining_qty' => max(0, $newRemainingQty),
            ]);

            if ($newRemainingQty <= 0) {
                $distribution->update(['status' => 'closed', 'closed_at' => now()]);
            }
        }
    }
}