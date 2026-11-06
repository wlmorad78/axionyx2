<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseInvoiceCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_partial_or_fully_received_invoice_reverses_stock(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'PIC' . uniqid(),
            'name_ar' => 'شركة اختبار إلغاء المشتريات',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $company = Company::findOrFail($companyId);
        $branch = Branch::create([
            'company_id' => $company->id,
            'code' => 'BR' . uniqid(),
            'name' => 'فرع الاختبار',
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'supplier_code' => 'SUP' . uniqid(),
            'supplier_name' => 'مورد الاختبار',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'code' => 'WH' . uniqid(),
            'name' => 'مخزن الاختبار',
            'is_active' => true,
        ]);
        $unit = Unit::create([
            'code' => 'U' . uniqid(),
            'name_ar' => 'وحدة',
            'is_active' => true,
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'code' => 'ITM' . uniqid(),
            'name_ar' => 'صنف الاختبار',
            'is_active' => true,
        ]);
        $type = InventoryTransactionType::create([
            'code' => 'PURCHASE_RECEIPT',
            'name' => 'استلام مشتريات',
            'effect' => 'addition',
            'is_active' => true,
        ]);

        foreach (['partial', 'posted'] as $index => $status) {
            $invoice = PurchaseInvoice::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id,
                'invoice_no' => 'PUR-CANCEL-' . $index . '-' . uniqid(),
                'invoice_date' => now()->toDateString(),
                'subtotal' => 10,
                'net_total' => 10,
                'status' => $status,
            ]);
            $transaction = InventoryTransaction::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'transaction_type_id' => $type->id,
                'warehouse_id' => $warehouse->id,
                'transaction_no' => 'INV-CANCEL-' . $index . '-' . uniqid(),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => PurchaseInvoice::class,
                'reference_id' => $invoice->id,
                'status' => 'posted',
            ]);
            InventoryTransactionItem::create([
                'inventory_transaction_id' => $transaction->id,
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'qty' => 2,
                'unit_cost' => 5,
                'total_cost' => 10,
            ]);

            $invoice->cancel();

            $this->assertSame('cancelled', $invoice->fresh()->status);
            $this->assertDatabaseMissing('inventory_transactions', [
                'id' => $transaction->id,
            ]);
            $this->assertDatabaseMissing('inventory_transaction_items', [
                'inventory_transaction_id' => $transaction->id,
            ]);

            try {
                $invoice->receiveQuantities([$item->id => 1]);
                $this->fail('A cancelled purchase invoice must not accept receipts.');
            } catch (\DomainException $error) {
                $this->assertSame(
                    'لا يمكن استلام فاتورة مشتريات ملغاة',
                    $error->getMessage()
                );
            }
        }
    }
}
