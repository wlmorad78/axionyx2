<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\ReturnOrder;
use App\Models\HR\Employee;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryTransactionItem;
use App\Models\Inventory\InventoryTransactionType;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\CRM\Customer;
use App\Support\ValidationRules;
use App\Services\UnitConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnOrderController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ReturnOrder::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->status_id) $query->where('status_id', $request->status_id);
        if ($request->return_type) $query->where('return_type', $request->return_type);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('return_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('return_order', 'store'));
        return response()->json(ReturnOrder::create($data), 201);
    }

    public function show(ReturnOrder $returnOrder)
    {
        return $returnOrder->load([
            'company', 'branch', 'warehouse', 'loadRequest', 'issueOrder',
            'employee', 'salesTerritory', 'receivedByEmployee', 'approvedByEmployee',
            'items.item', 'items.unit',
        ]);
    }

    public function update(Request $request, ReturnOrder $returnOrder)
    {
        $data = $request->validate(ValidationRules::for('return_order', 'update', $returnOrder));
        $returnOrder->update($data);
        return response()->json($returnOrder);
    }

    public function destroy(ReturnOrder $returnOrder)
    {
        $returnOrder->delete();
        return response()->json(null, 204);
    }

    public function approve(Request $request, ReturnOrder $returnOrder)
    {
        if ($returnOrder->status_id !== 'pending') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø§Ù„Ù…ÙˆØ§ÙÙ‚Ø© Ø¹Ù„Ù‰ Ø£Ù…Ø± Ø¨Ø­Ø§Ù„Ø© ' . $returnOrder->status_id], 422);
        }

        $user = $request->user();
        $employee = Employee::where('email', $user->email)->first();
        $returnOrder->load('items.item');
        $unitService = app(UnitConversionService::class);

        DB::transaction(function () use ($returnOrder, $request, $employee, $user, $unitService) {
            $returnOrder->update([
                'status_id' => 'approved',
                'approved_by' => $employee?->id,
                'approved_at' => now(),
                'notes' => $request->notes ?? 'ØªÙ…Øª Ø§Ù„Ù…ÙˆØ§ÙÙ‚Ø© Ù…Ù† Ø£Ù…ÙŠÙ† Ø§Ù„Ù…Ø®Ø²Ù†',
            ]);

            $type = InventoryTransactionType::firstOrCreate(
                ['code' => 'RETURN'],
                ['name' => 'Return Order', 'effect' => 'addition', 'is_active' => true]
            );

            $txn = InventoryTransaction::create([
                'company_id' => $returnOrder->company_id,
                'warehouse_id' => $returnOrder->warehouse_id,
                'transaction_type_id' => $type->id,
                'transaction_no' => InventoryTransaction::nextTransactionNo($returnOrder->company_id),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => ReturnOrder::class,
                'reference_id' => $returnOrder->id,
                'notes' => "Ø§Ø±ØªØ¬Ø§Ø¹ Ù…Ù† Ø§Ù„Ù…Ù†Ø¯ÙˆØ¨ {$returnOrder->return_no}",
                'status' => 'posted',
                'created_by' => $employee?->id,
            ]);

            foreach ($returnOrder->items as $item) {
                $unitId = $item->item_unit_id ?? $item->item?->base_unit_id;
                if (!$unitId) {
                    $unitId = App\Models\Unit::first()?->id;
                }
                $conversionFactor = $unitService->getConversionFactor($item->item_id, $unitId);
                $qtyInBase = $unitService->toBase($item->item_id, $unitId, $item->returned_quantity);
                InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $unitId,
                    'conversion_factor' => $conversionFactor,
                    'qty' => $qtyInBase,
                    'unit_cost' => $item->sales_price,
                    'total_cost' => $item->line_total,
                    'from_location_type' => 'rep',
                    'from_location_id'   => $returnOrder->employee_id,
                    'to_location_type'   => 'warehouse',
                    'to_location_id'     => $returnOrder->warehouse_id,
                ]);
            }

            if (($returnOrder->return_purpose ?? 'salesman_return') === 'salesman_return' && $returnOrder->salesman_account_id) {
                $salesmanAccount = \App\Models\Sales\SalesmanAccount::find($returnOrder->salesman_account_id);
                if ($salesmanAccount) {
                    $totalReturns = $returnOrder->total_amount;

                    $salesmanDebt = \App\Models\Sales\SalesmanDebt::create([
                        'company_id' => $returnOrder->company_id,
                        'branch_id' => $returnOrder->branch_id,
                        'salesman_id' => $returnOrder->employee_id,
                        'salesman_account_id' => $salesmanAccount->id,
                        'return_authorization_id' => null,
                        'salesman_assignment_id' => null,
                        'debt_date' => now()->toDateString(),
                        'total_sales' => 0,
                        'total_returns' => $totalReturns,
                        'gross_debt' => $totalReturns,
                        'total_paid' => 0,
                        'remaining_debt' => $totalReturns,
                        'writeoff_amount' => 0,
                        'status' => 'active',
                        'notes' => "Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ù…Ù† Ø§ØºÙ„Ø§Ù‚ Ø¥Ø°Ù† Ø§Ù„ØªØ­Ù…ÙŠÙ„ {$returnOrder->return_no}",
                        'created_by' => $employee?->id,
                    ]);

                    $returnOrder->update(['salesman_debt_id' => $salesmanDebt->id]);

                    $salesmanAccount->update([
                        'total_debts' => $salesmanAccount->total_debts + $totalReturns,
                    ]);

                    \App\Models\Sales\SalesmanAccountMovement::create([
                        'company_id' => $returnOrder->company_id,
                        'branch_id' => $returnOrder->branch_id,
                        'salesman_account_id' => $salesmanAccount->id,
                        'salesman_id' => $returnOrder->employee_id,
                        'movement_date' => now()->toDateString(),
                        'movement_type' => 'debt_creation',
                        'reference_type' => \App\Models\Sales\SalesmanDebt::class,
                        'reference_id' => $salesmanDebt->id,
                        'document_no' => $salesmanDebt->debt_no,
                        'debit' => $totalReturns,
                        'credit' => 0,
                        'balance' => $salesmanAccount->current_balance,
                        'description' => "Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø¯ÙŠÙˆÙ†ÙŠØ© Ù…Ù† Ø§ØºÙ„Ø§Ù‚ Ø§Ù„Ø¥Ø°Ù† {$returnOrder->return_no}",
                        'notes' => null,
                        'created_by' => $employee?->id,
                    ]);
                }
            }

            $customer = Customer::where('company_id', $user->company_id)->first();

            $salesInvoice = SalesInvoice::create([
                'company_id' => $returnOrder->company_id,
                'warehouse_id' => $returnOrder->warehouse_id,
                'customer_id' => $customer?->id,
                'sales_rep_id' => $returnOrder->employee_id,
                'invoice_date' => now()->toDateString(),
                'invoice_time' => now()->format('H:i:s'),
                'subtotal' => 0,
                'item_discount_total' => 0,
                'invoice_discount_total' => 0,
                'tax_total' => 0,
                'incentive_total' => 0,
                'net_total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'status' => 'approved',
                'notes' => "Ø£Ù…Ø± Ø¨ÙŠØ¹ Ù…Ù† Ø§ØºÙ„Ø§Ù‚ Ø§Ù„Ø¥Ø°Ù† {$returnOrder->return_no}",
                'created_by' => $employee?->id,
                'approved_by' => $employee?->id,
            ]);

            $subtotal = 0;
            foreach ($returnOrder->items as $item) {
                $lineTotal = $item->line_total;
                $subtotal += $lineTotal;
                $unitId = $item->item_unit_id ?? $item->item?->base_unit_id;
                if (!$unitId) {
                    $unitId = App\Models\Unit::first()?->id;
                }

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $salesInvoice->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $unitId,
                    'warehouse_id' => $returnOrder->warehouse_id,
                    'qty' => $item->returned_quantity,
                    'bonus_qty' => 0,
                    'price' => $item->sales_price,
                    'gross_amount' => $lineTotal,
                    'discount_type' => null,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                    'net_amount' => $lineTotal,
                ]);
            }

            $tax = $subtotal * 0.15;
            $salesInvoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $tax,
                'net_total' => $subtotal + $tax,
                'remaining_amount' => $subtotal + $tax,
            ]);
        });

        return response()->json([
            'message' => 'ØªÙ…Øª Ø§Ù„Ù…ÙˆØ§ÙÙ‚Ø© Ø¹Ù„Ù‰ Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹ ÙˆØ¥Ù†Ø´Ø§Ø¡ Ø£Ù…Ø± Ø§Ù„Ø¨ÙŠØ¹ Ø¨Ù†Ø¬Ø§Ø­',
            'return_order' => $returnOrder->fresh()->load(['items.item', 'items.unit', 'approvedByEmployee']),
        ]);
    }

    public function reject(Request $request, ReturnOrder $returnOrder)
    {
        if ($returnOrder->status_id !== 'pending') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø±ÙØ¶ Ø£Ù…Ø± Ø¨Ø­Ø§Ù„Ø© ' . $returnOrder->status_id], 422);
        }

        $returnOrder->update([
            'status_id' => 'cancelled',
            'notes' => $request->notes ?? 'Ù…Ø±ÙÙˆØ¶ Ù…Ù† Ø£Ù…ÙŠÙ† Ø§Ù„Ù…Ø®Ø²Ù†',
        ]);

        return response()->json([
            'message' => 'ØªÙ… Ø±ÙØ¶ Ø·Ù„Ø¨ Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹',
            'return_order' => $returnOrder->fresh()->load(['items.item', 'items.unit']),
        ]);
    }

    public function reopen(Request $request, ReturnOrder $returnOrder)
    {
        if ($returnOrder->status_id !== 'approved') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø¥Ø¹Ø§Ø¯Ø© ÙØªØ­ Ø¥Ù„Ø§ Ø§Ù„Ø·Ù„Ø¨Ø§Øª Ø§Ù„Ù…Ø¹ØªÙ…Ø¯Ø©'], 422);
        }

        DB::transaction(function () use ($returnOrder) {
            InventoryTransaction::where('reference_type', ReturnOrder::class)
                ->where('reference_id', $returnOrder->id)
                ->each(function ($txn) {
                    $txn->items()->delete();
                    $txn->forceDelete();
                });

            $returnOrder->update([
                'status_id' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'notes' => 'ØªÙ…Øª Ø¥Ø¹Ø§Ø¯Ø© Ø§Ù„ÙØªØ­',
            ]);
        });

        return response()->json([
            'message' => 'ØªÙ…Øª Ø¥Ø¹Ø§Ø¯Ø© ÙØªØ­ Ø·Ù„Ø¨ Ø§Ù„Ø§Ø±ØªØ¬Ø§Ø¹',
            'return_order' => $returnOrder->fresh()->load(['items.item', 'items.unit']),
        ]);
    }

    public function restore(int $id)
    {
        $m = ReturnOrder::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        ReturnOrder::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('return_order', 'store');
    }
}
