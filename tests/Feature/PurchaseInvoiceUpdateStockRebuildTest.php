<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Purchase\PurchaseInvoiceController;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseInvoiceUpdateStockRebuildTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, int>
     */
    private function seedWorld(): array
    {
        $now = now();
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'PUUPD' . uniqid(),
            'name_ar' => 'شركة اختبار تعديل المشتريات',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'code' => 'B' . uniqid(),
            'name' => 'فرع الاختبار',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $companyId,
            'supplier_code' => 'SUP' . uniqid(),
            'supplier_name' => 'مورد الاختبار',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'code' => 'WH' . uniqid(),
            'name' => 'مخزن الاختبار',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $unitId = DB::table('units')->insertGetId([
            'code' => 'U' . uniqid(),
            'name_ar' => 'وحدة',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $itemId = DB::table('items')->insertGetId([
            'company_id' => $companyId,
            'code' => 'ITM' . uniqid(),
            'name_ar' => 'صنف الاختبار',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('inventory_transaction_types')->insert([
            'code' => 'PURCHASE_RECEIPT',
            'name' => 'استلام مشتريات',
            'effect' => 'addition',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return compact('companyId', 'branchId', 'supplierId', 'warehouseId', 'unitId', 'itemId');
    }

    /**
     * فاتورة محذوف الوضع (posted) مع صنف وحركة مخزون قائمة.
     *
     * @param array<string, int> $world
     * @param array<string, mixed> $overrides
     */
    private function makeInvoice(array $world, array $overrides = []): PurchaseInvoice
    {
        $invoice = PurchaseInvoice::create(array_merge([
            'company_id' => $world['companyId'],
            'branch_id' => $world['branchId'],
            'warehouse_id' => $world['warehouseId'],
            'supplier_id' => $world['supplierId'],
            'invoice_no' => 'PUR-UPD-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 500,
            'net_total' => 500,
            'status' => 'posted',
        ], $overrides));

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'item_id' => $world['itemId'],
            'unit_id' => $world['unitId'],
            'qty' => 100,
            'received_qty' => 100,
            'conversion_factor' => 1,
            'base_quantity' => 100,
            'price' => 5,
            'net_amount' => 500,
        ]);

        $transactionId = InventoryTransaction::create([
            'company_id' => $world['companyId'],
            'branch_id' => $world['branchId'],
            'transaction_type_id' => InventoryTransactionType::firstWhere('code', 'PURCHASE_RECEIPT')->id,
            'warehouse_id' => $world['warehouseId'],
            'transaction_no' => 'INV-UPD-' . uniqid(),
            'transaction_date' => now()->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'status' => 'posted',
        ])->id;

        InventoryTransactionItem::create([
            'inventory_transaction_id' => $transactionId,
            'item_id' => $world['itemId'],
            'unit_id' => $world['unitId'],
            'conversion_factor' => 1,
            'qty' => 100,
            'unit_cost' => 5,
            'total_cost' => 500,
        ]);

        return $invoice;
    }

    private function stockFor(PurchaseInvoice $invoice): float
    {
        $txns = InventoryTransaction::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $invoice->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        return (float) InventoryTransactionItem::whereIn('inventory_transaction_id', $txns)->sum('qty');
    }

    private function updateRequest(PurchaseInvoice $invoice, array $payload): Request
    {
        return Request::create('/api/purchase-invoices/' . $invoice->id, 'PUT', array_merge([
            'company_id' => $invoice->company_id,
            'supplier_id' => $invoice->supplier_id,
            'invoice_date' => $invoice->invoice_date->toDateString(),
        ], $payload));
    }

    public function test_editing_quantity_rebuilds_stock_from_received_and_caps_it_to_new_qty(): void
    {
        $world = $this->seedWorld();
        $invoice = $this->makeInvoice($world);

        // العميل لا يرسل received_qty: يجب ألا يضيع الاستلام المسجّل
        $response = app(PurchaseInvoiceController::class)->update(
            $this->updateRequest($invoice, [
                'subtotal' => 480,
                'net_total' => 480,
                'items' => [[
                    'item_id' => $world['itemId'],
                    'unit_id' => $world['unitId'],
                    'qty' => 80,
                    'price' => 6,
                ]],
            ]),
            $invoice->fresh()
        );

        $this->assertSame(200, $response->getStatusCode());

        $invoice->refresh();
        $line = PurchaseInvoiceItem::where('purchase_invoice_id', $invoice->id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->assertSame(480.0, (float) $invoice->net_total);
        $this->assertSame('posted', $invoice->status);
        $this->assertSame(80.0, (float) $line->qty);
        // الاستلام المسجّل (100) لا يتجاوز الكمية الجديدة (80)
        $this->assertSame(80.0, (float) $line->received_qty);
        $this->assertSame(480.0, (float) $line->net_amount);
        $this->assertSame(80.0, $this->stockFor($invoice));
        $this->assertSame(1, InventoryTransaction::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $invoice->id)
            ->whereNull('deleted_at')
            ->count());
    }

    public function test_invoice_without_recorded_receipt_keeps_its_existing_stock(): void
    {
        $world = $this->seedWorld();
        $invoice = $this->makeInvoice($world);

        // فاتورة مثل PU-00069: عليها مخزون لكن الاستلام = صفر
        DB::table('purchase_invoice_items')
            ->where('purchase_invoice_id', $invoice->id)
            ->update(['received_qty' => 0]);
        DB::table('inventory_transaction_items')
            ->where('inventory_transaction_id', InventoryTransaction::where('reference_id', $invoice->id)->value('id'))
            ->update(['qty' => 6050, 'unit_cost' => 5, 'total_cost' => 30250]);
        DB::table('purchase_invoices')->where('id', $invoice->id)->update(['subtotal' => 6050, 'net_total' => 6050]);

        $this->assertSame(6050.0, $this->stockFor($invoice->fresh()));

        $response = app(PurchaseInvoiceController::class)->update(
            $this->updateRequest($invoice->fresh(), [
                'subtotal' => 25000,
                'net_total' => 25000,
                'items' => [[
                    'item_id' => $world['itemId'],
                    'unit_id' => $world['unitId'],
                    'qty' => 5000,
                    'price' => 5,
                    'received_qty' => 0,
                ]],
            ]),
            $invoice->fresh()
        );

        $this->assertSame(200, $response->getStatusCode());

        $invoice->refresh();
        $line = PurchaseInvoiceItem::where('purchase_invoice_id', $invoice->id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->assertSame(5000.0, (float) $line->qty);
        $this->assertSame(0.0, (float) $line->received_qty);
        // المخزون القائم لا يُحذف لغياب الاستلام
        $this->assertSame(6050.0, $this->stockFor($invoice));
        $this->assertSame('posted', $invoice->status);
    }

    public function test_partially_received_invoice_becomes_partial_after_edit(): void
    {
        $world = $this->seedWorld();
        $invoice = $this->makeInvoice($world);

        DB::table('purchase_invoice_items')
            ->where('purchase_invoice_id', $invoice->id)
            ->update(['received_qty' => 40]);
        DB::table('inventory_transaction_items')
            ->where('inventory_transaction_id', InventoryTransaction::where('reference_id', $invoice->id)->value('id'))
            ->update(['qty' => 40, 'total_cost' => 200]);

        $response = app(PurchaseInvoiceController::class)->update(
            $this->updateRequest($invoice->fresh(), [
                'subtotal' => 600,
                'net_total' => 600,
                'items' => [[
                    'item_id' => $world['itemId'],
                    'unit_id' => $world['unitId'],
                    'qty' => 100,
                    'price' => 6,
                ]],
            ]),
            $invoice->fresh()
        );

        $this->assertSame(200, $response->getStatusCode());

        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame(40.0, $this->stockFor($invoice));
    }

    public function test_cancelled_invoice_cannot_be_edited(): void
    {
        $world = $this->seedWorld();
        $invoice = $this->makeInvoice($world, ['status' => 'cancelled']);

        $response = app(PurchaseInvoiceController::class)->update(
            $this->updateRequest($invoice, [
                'subtotal' => 100,
                'net_total' => 100,
                'items' => [[
                    'item_id' => $world['itemId'],
                    'unit_id' => $world['unitId'],
                    'qty' => 1,
                    'price' => 100,
                ]],
            ]),
            $invoice->fresh()
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('لا يمكن تعديل فاتورة ملغاة', $response->getData(true)['message']);
    }
}
