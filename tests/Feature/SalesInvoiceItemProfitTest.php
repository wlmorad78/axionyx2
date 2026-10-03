<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Services\CostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceItemProfitTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(): SalesInvoice
    {
        $company = Company::create([
            'code' => 'PRF' . uniqid(),
            'name_ar' => 'شركة الأرباح',
            'is_active' => true,
        ]);

        return SalesInvoice::create([
            'company_id' => $company->id,
            'invoice_no' => 'INV-PROFIT-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 500,
            'net_total' => 500,
            'status' => 'draft',
        ]);
    }

    private function makeItem(Company $company): Item
    {
        return Item::create([
            'company_id' => $company->id,
            'code' => 'ITM' . uniqid(),
            'name_ar' => 'صنف',
            'is_active' => true,
        ]);
    }

    public function test_profit_is_computed_on_create_and_update(): void
    {
        $invoice = $this->makeInvoice();
        $item = $this->makeItem($invoice->company);

        $line = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'qty' => 10,
            'price' => 50,
            'gross_amount' => 500,
            'net_amount' => 500,
            'unit_cost' => 30,
            'total_cost' => 300,
        ]);

        $this->assertEquals(200.0, (float) $line->profit);

        $line->update(['total_cost' => 400]);
        $this->assertEquals(100.0, (float) $line->fresh()->profit);

        $line->update(['net_amount' => 350]);
        $this->assertEquals(-50.0, (float) $line->fresh()->profit);
    }

    public function test_recalculate_all_profits_fixes_stale_rows(): void
    {
        $invoice = $this->makeInvoice();
        $item = $this->makeItem($invoice->company);

        $line = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'qty' => 4,
            'price' => 25,
            'gross_amount' => 100,
            'net_amount' => 100,
            'unit_cost' => 20,
            'total_cost' => 80,
        ]);

        \DB::table('sales_invoice_items')->where('id', $line->id)->update(['profit' => 0]);

        $updated = app(CostingService::class)->recalculateAllProfits();
        $this->assertGreaterThan(0, $updated);
        $this->assertEquals(20.0, (float) SalesInvoiceItem::find($line->id)->profit);
    }

    public function test_recalculate_sales_costs_also_updates_profit(): void
    {
        $invoice = $this->makeInvoice();
        $company = $invoice->company;
        $item = $this->makeItem($company);

        $unit = \App\Models\Unit::create([
            'company_id' => $company->id,
            'code' => 'U' . uniqid(),
            'name_ar' => 'وحدة',
            'is_active' => true,
        ]);

        \App\Models\ItemUnit::create([
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'conversion_factor' => 1,
            'is_default' => true,
            'purchase_price' => 60,
        ]);

        $line = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'qty' => 2,
            'price' => 100,
            'gross_amount' => 200,
            'net_amount' => 200,
            'unit_cost' => 0,
            'total_cost' => 0,
        ]);

        \DB::table('sales_invoice_items')->where('id', $line->id)->update(['profit' => 0]);

        app(CostingService::class)->recalculateAllSalesCosts();

        $fresh = SalesInvoiceItem::find($line->id);
        $this->assertEquals(60.0, (float) $fresh->unit_cost);
        $this->assertEquals(120.0, (float) $fresh->total_cost);
        $this->assertEquals(80.0, (float) $fresh->profit);
    }
}
