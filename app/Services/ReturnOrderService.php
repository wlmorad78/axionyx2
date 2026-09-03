<?php

namespace App\Services;

use App\Models\ReturnOrder;
use App\Models\Employee;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Customer;
use App\Models\LoadRequest;
use App\Models\SalesmanAccount;
use App\Models\SalesmanDebt;
use App\Models\SalesmanAccountMovement;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class ReturnOrderService
{
    protected UnitConversionService $unitService;

    public function __construct(UnitConversionService $unitService)
    {
        $this->unitService = $unitService;
    }

    public function approve(ReturnOrder $returnOrder, ?int $employeeId = null, ?string $notes = null): ReturnOrder
    {
        if ($returnOrder->status_id !== 'pending') {
            throw new \RuntimeException('لا يمكن الموافقة على أمر بحالة ' . $returnOrder->status_id);
        }

        $returnOrder->load('items.item');

        DB::transaction(function () use ($returnOrder, $employeeId, $notes) {
            $returnOrder->update([
                'status_id'   => 'approved',
                'approved_by' => $employeeId,
                'approved_at' => now(),
                'notes'       => $notes ?? 'تمت الموافقة من أمين المخزن',
            ]);

            // Close load request
            if ($returnOrder->load_request_id) {
                $this->closeLoadRequest($returnOrder->load_request_id);
            }

            // Create inventory transaction
            $this->createInventoryTransaction($returnOrder, $employeeId);

            // Create salesman debt if applicable
            $this->createSalesmanDebt($returnOrder, $employeeId);

            // Create sales invoice
            $this->createSalesInvoice($returnOrder, $employeeId);
        });

        return $returnOrder->fresh();
    }

    public function approveAll(?int $companyId = null): array
    {
        $query = ReturnOrder::where('status_id', 'pending');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $orders = $query->get();
        $approved = 0;
        $failed = 0;
        $errors = [];

        foreach ($orders as $order) {
            try {
                $this->approve($order);
                $approved++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "{$order->return_no}: {$e->getMessage()}";
            }
        }

        return [
            'total'    => $orders->count(),
            'approved' => $approved,
            'failed'   => $failed,
            'errors'   => $errors,
        ];
    }

    protected function closeLoadRequest(int $loadRequestId): void
    {
        $loadRequest = LoadRequest::find($loadRequestId);
        if (!$loadRequest) return;

        LoadRequest::where('id', $loadRequestId)
            ->update(['status' => 'closed']);

        LoadRequest::where('parent_load_request_id', $loadRequestId)
            ->where('status', '!=', 'closed')
            ->update(['status' => 'closed']);

        if ($loadRequest->parent_load_request_id) {
            $hasOpenChildren = LoadRequest::where('parent_load_request_id', $loadRequest->parent_load_request_id)
                ->where('id', '!=', $loadRequestId)
                ->where('status', '!=', 'closed')
                ->exists();

            if (!$hasOpenChildren) {
                LoadRequest::where('id', $loadRequest->parent_load_request_id)
                    ->update(['status' => 'closed']);
            }
        }
    }

    protected function createInventoryTransaction(ReturnOrder $returnOrder, ?int $employeeId): void
    {
        $type = InventoryTransactionType::firstOrCreate(
            ['code' => 'RETURN'],
            ['name' => 'Return Order', 'effect' => 'addition', 'is_active' => true]
        );

        $txn = InventoryTransaction::create([
            'company_id'          => $returnOrder->company_id,
            'warehouse_id'        => $returnOrder->warehouse_id,
            'transaction_type_id' => $type->id,
            'transaction_no'      => InventoryTransaction::nextTransactionNo($returnOrder->company_id),
            'transaction_date'    => now()->toDateString(),
            'transaction_time'    => now()->format('H:i:s'),
            'reference_type'      => ReturnOrder::class,
            'reference_id'        => $returnOrder->id,
            'notes'               => "اردتاج من المنتج {$returnOrder->return_no}",
            'status'              => 'posted',
            'created_by'          => $employeeId,
        ]);

        foreach ($returnOrder->items as $item) {
            $unitId = $item->item_unit_id ?? $item->item?->base_unit_id;
            if (!$unitId) {
                $unitId = Unit::first()?->id;
            }
            $conversionFactor = $this->unitService->getConversionFactor($item->item_id, $unitId);
            $qtyInBase = $this->unitService->toBase($item->item_id, $unitId, $item->returned_quantity);

            InventoryTransactionItem::create([
                'inventory_transaction_id' => $txn->id,
                'item_id'                  => $item->item_id,
                'unit_id'                  => $unitId,
                'conversion_factor'        => $conversionFactor,
                'qty'                      => $qtyInBase,
                'unit_cost'                => $item->sales_price,
                'total_cost'               => $item->line_total,
                'from_location_type'       => 'rep',
                'from_location_id'         => $returnOrder->user_id,
                'to_location_type'         => 'warehouse',
                'to_location_id'           => $returnOrder->warehouse_id,
            ]);
        }
    }

    protected function createSalesmanDebt(ReturnOrder $returnOrder, ?int $employeeId): void
    {
        if (($returnOrder->return_purpose ?? 'salesman_return') !== 'salesman_return' || !$returnOrder->salesman_account_id) {
            return;
        }

        $salesmanAccount = SalesmanAccount::find($returnOrder->salesman_account_id);
        if (!$salesmanAccount) return;

        $totalReturns = $returnOrder->total_amount;

        $salesmanDebt = SalesmanDebt::create([
            'company_id'               => $returnOrder->company_id,
            'branch_id'                => $returnOrder->branch_id,
            'salesman_id'              => $returnOrder->user_id,
            'salesman_account_id'      => $salesmanAccount->id,
            'return_authorization_id'  => null,
            'salesman_assignment_id'   => null,
            'debt_date'                => now()->toDateString(),
            'total_sales'              => 0,
            'total_returns'            => $totalReturns,
            'gross_debt'               => $totalReturns,
            'total_paid'               => 0,
            'remaining_debt'           => $totalReturns,
            'writeoff_amount'          => 0,
            'status'                   => 'active',
            'notes'                    => "مديونية من اغلق اذن التحصيل {$returnOrder->return_no}",
            'created_by'               => $employeeId,
        ]);

        $returnOrder->update(['salesman_debt_id' => $salesmanDebt->id]);

        $salesmanAccount->update([
            'total_debts' => $salesmanAccount->total_debts + $totalReturns,
        ]);

        SalesmanAccountMovement::create([
            'company_id'          => $returnOrder->company_id,
            'branch_id'           => $returnOrder->branch_id,
            'salesman_account_id' => $salesmanAccount->id,
            'salesman_id'         => $returnOrder->user_id,
            'movement_date'       => now()->toDateString(),
            'movement_type'       => 'debt_creation',
            'reference_type'      => SalesmanDebt::class,
            'reference_id'        => $salesmanDebt->id,
            'document_no'         => $salesmanDebt->debt_no,
            'debit'               => $totalReturns,
            'credit'              => 0,
            'balance'             => $salesmanAccount->current_balance,
            'description'         => "انشاء مديونية من اغلق الاذن {$returnOrder->return_no}",
            'notes'               => null,
            'created_by'          => $employeeId,
        ]);
    }

    protected function createSalesInvoice(ReturnOrder $returnOrder, ?int $employeeId): void
    {
        $customer = Customer::where('company_id', $returnOrder->company_id)->first();

        $salesInvoice = new SalesInvoice([
            'company_id'             => $returnOrder->company_id,
            'branch_id'              => $returnOrder->branch_id,
            'warehouse_id'           => $returnOrder->warehouse_id,
            'customer_id'            => $customer?->id,
            'sales_rep_id'           => $returnOrder->user_id,
            'invoice_date'           => now()->toDateString(),
            'invoice_time'           => now()->format('H:i:s'),
            'subtotal'               => 0,
            'item_discount_total'    => 0,
            'invoice_discount_total' => 0,
            'tax_total'              => 0,
            'incentive_total'        => 0,
            'net_total'              => 0,
            'paid_amount'            => 0,
            'remaining_amount'       => 0,
            'status'                 => 'approved',
            'notes'                  => "امر بيع من اغلق اذن {$returnOrder->return_no}",
            'created_by'             => $employeeId,
            'approved_by'            => $employeeId,
        ]);
        $salesInvoice->invoice_no = $salesInvoice->generateNumber();
        $salesInvoice->save();

        $subtotal = 0;
        foreach ($returnOrder->items as $item) {
            $lineTotal = $item->line_total;
            $subtotal += $lineTotal;
            $unitId = $item->item_unit_id ?? $item->item?->base_unit_id;
            if (!$unitId) {
                $unitId = Unit::first()?->id;
            }

            SalesInvoiceItem::create([
                'sales_invoice_id' => $salesInvoice->id,
                'item_id'          => $item->item_id,
                'unit_id'          => $unitId,
                'warehouse_id'     => $returnOrder->warehouse_id,
                'qty'              => $item->returned_quantity,
                'bonus_qty'        => 0,
                'price'            => $item->sales_price,
                'gross_amount'     => $lineTotal,
                'discount_type'    => null,
                'discount_value'   => 0,
                'discount_amount'  => 0,
                'tax_percent'      => 0,
                'tax_amount'       => 0,
                'net_amount'       => $lineTotal,
            ]);
        }

        $tax = $subtotal * 0.15;
        $salesInvoice->update([
            'subtotal'          => $subtotal,
            'tax_total'         => $tax,
            'net_total'         => $subtotal + $tax,
            'remaining_amount'  => $subtotal + $tax,
        ]);
    }
}
