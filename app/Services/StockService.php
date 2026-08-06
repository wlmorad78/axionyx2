<?php

namespace App\Services;

use App\Models\DispatchOrder;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\ProductStock;
use App\Models\RepReturnOrder;
use App\Models\RepresentativeStock;
use App\Models\RepresentativeStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function approveDispatchOrder(DispatchOrder $order, ?int $approvedBy = null): DispatchOrder
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages(['status' => ['أمر الصرف معتمد مسبقاً']]);
        }

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => ['لا يمكن اعتماد أمر ملغي']]);
        }

        $order->load('items.product');

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages(['items' => ['أمر الصرف لا يحتوي على أصناف']]);
        }

        return DB::transaction(function () use ($order, $approvedBy) {
            foreach ($order->items as $item) {
                $this->deductWarehouseStock(
                    $order->warehouse_id,
                    $item->product_id,
                    $item->quantity,
                    'dispatch_to_rep',
                    DispatchOrder::class,
                    $order->id,
                    $item->unit_cost,
                    $approvedBy
                );

                $this->addRepresentativeStock(
                    $order->representative_id,
                    $item->product_id,
                    $item->quantity,
                    'dispatch_in',
                    DispatchOrder::class,
                    $order->id,
                    $approvedBy
                );
            }

            $order->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $order->fresh(['items.product', 'warehouse', 'representative', 'creator', 'approver']);
        });
    }

    public function releaseDispatchOrder(DispatchOrder $order, ?int $userId = null): DispatchOrder
    {
        if (! in_array($order->status, ['draft'])) {
            throw ValidationException::withMessages(['status' => ['لا يمكن إرسال هذا الأمر، الحالة الحالية: ' . $order->status]]);
        }

        $order->load('items.product');

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages(['items' => ['أمر التحميل لا يحتوي على أصناف']]);
        }

        return DB::transaction(function () use ($order, $userId) {
            $errors = [];
            $warehouseId = $order->warehouse_id;

            foreach ($order->items as $item) {
                $stock = ProductStock::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $item->product_id)
                    ->first();

                $available = $stock?->quantity ?? 0;
                $msg = "releaseDispatchOrder: product={$item->product_id}, warehouse={$warehouseId}, stock_exists=" . ($stock ? 'yes' : 'no') . ", available={$available}, requested={$item->quantity}, available_type=" . gettype($available) . ", requested_type=" . gettype($item->quantity) . ", comparison=" . ($available < $item->quantity ? 'insufficient' : 'sufficient');
                logger($msg);
                file_put_contents(__DIR__ . '/../../storage/debug_stock.txt', date('Y-m-d H:i:s') . " | $msg\n", FILE_APPEND);
                if ($available < $item->quantity) {
                    $productName = $item->product?->name ?? "منتج #{$item->product_id}";
                    $errors[] = "{$productName}: المتاح {$available}، المطلوب {$item->quantity}";
                }
            }

            if (! empty($errors)) {
                $msg = implode("\n", array_merge(['الكميات غير كافية في المخزن:'], $errors));
                logger()->error("Stock check failed: " . $msg . " | order_id={$order->id} warehouse_id={$warehouseId}");
                throw new \RuntimeException($msg);
            }

            foreach ($order->items as $item) {
                $this->deductWarehouseStock(
                    $warehouseId,
                    $item->product_id,
                    $item->quantity,
                    'dispatch_to_rep',
                    DispatchOrder::class,
                    $order->id,
                    $item->unit_cost,
                    $userId
                );

                $this->addRepresentativeStock(
                    $order->representative_id,
                    $item->product_id,
                    $item->quantity,
                    'dispatch_in',
                    DispatchOrder::class,
                    $order->id,
                    $userId
                );
            }

            $order->update([
                'status' => 'released',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            return $order->fresh(['items.product', 'warehouse', 'representative', 'creator', 'approver', 'dispatcher']);
        });
    }

    public function approveRepReturnOrder(RepReturnOrder $order, ?int $approvedBy = null): RepReturnOrder
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages(['status' => ['أمر الارتجاع معتمد مسبقاً']]);
        }

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => ['لا يمكن اعتماد أمر ملغي']]);
        }

        $order->load('items.product');

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages(['items' => ['أمر الارتجاع لا يحتوي على أصناف']]);
        }

        return DB::transaction(function () use ($order, $approvedBy) {
            foreach ($order->items as $item) {
                $this->deductRepresentativeStock(
                    $order->representative_id,
                    $item->product_id,
                    $item->quantity,
                    'return_out',
                    RepReturnOrder::class,
                    $order->id,
                    $approvedBy
                );

                $this->addWarehouseStock(
                    $order->warehouse_id,
                    $item->product_id,
                    $item->quantity,
                    'return_from_rep',
                    RepReturnOrder::class,
                    $order->id,
                    $item->unit_cost,
                    $approvedBy
                );
            }

            $order->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $order->fresh(['items.product', 'warehouse', 'representative', 'creator', 'approver']);
        });
    }

    public function deductForInvoiceSale(Invoice $invoice, ?int $createdBy = null): void
    {
        if (! $invoice->representative_id) {
            return;
        }

        $invoice->load('items');

        foreach ($invoice->items as $item) {
            $this->deductRepresentativeStock(
                $invoice->representative_id,
                $item->product_id,
                $item->quantity,
                'sale_out',
                Invoice::class,
                $invoice->id,
                $createdBy ?? $invoice->created_by
            );
        }
    }

    public function transferWarehouseStock(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        float $quantity,
        float $unitCost = 0,
        ?int $createdBy = null,
        ?string $movementDate = null
    ): void {
        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => ['لا يمكن نقل المخزون لنفس المخزن.'],
            ]);
        }

        DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $quantity, $unitCost, $createdBy, $movementDate) {
            $this->deductWarehouseStock(
                $fromWarehouseId,
                $productId,
                $quantity,
                'transfer_out',
                'warehouse_transfer',
                $toWarehouseId,
                $unitCost,
                $createdBy
            );

            $this->addWarehouseStock(
                $toWarehouseId,
                $productId,
                $quantity,
                'transfer_in',
                'warehouse_transfer',
                $fromWarehouseId,
                $unitCost,
                $createdBy
            );
        });
    }

    public function deductWarehouseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        float $unitCost = 0,
        ?int $createdBy = null
    ): void {
        $stock = ProductStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $stock || $stock->quantity < $quantity) {
            $available = $stock?->quantity ?? 0;
            throw ValidationException::withMessages([
                'quantity' => ["الكمية غير كافية في المخزن. المتاح: {$available}، المطلوب: {$quantity}"],
            ]);
        }

        $stock->decrement('quantity', $quantity);

        InventoryMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'movement_date' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }

    public function addWarehouseStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        float $unitCost = 0,
        ?int $createdBy = null
    ): void {
        $stock = ProductStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->increment('quantity', $quantity);
        } else {
            ProductStock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        InventoryMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'movement_date' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }

    public function addRepresentativeStock(
        int $representativeId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): void {
        $stock = RepresentativeStock::query()
            ->where('representative_id', $representativeId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->increment('quantity', $quantity);
        } else {
            RepresentativeStock::create([
                'representative_id' => $representativeId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        RepresentativeStockMovement::create([
            'representative_id' => $representativeId,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'movement_date' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }

    public function deductRepresentativeStock(
        int $representativeId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): void {
        $stock = RepresentativeStock::query()
            ->where('representative_id', $representativeId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $stock || $stock->quantity < $quantity) {
            $available = $stock?->quantity ?? 0;
            throw ValidationException::withMessages([
                'quantity' => ["الكمية غير كافية لدى المندوب. المتاح: {$available}، المطلوب: {$quantity}"],
            ]);
        }

        $stock->decrement('quantity', $quantity);

        RepresentativeStockMovement::create([
            'representative_id' => $representativeId,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'movement_date' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);
    }
}
