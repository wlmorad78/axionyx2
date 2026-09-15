<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateCosts extends Command
{
    protected $signature = 'costing:recalculate {--purchase-price= : Set purchase_price for all default units}';
    protected $description = 'Recalculate costs for all sales invoices and optionally update purchase prices';

    public function handle(): int
    {
        $purchasePrice = $this->option('purchase-price');

        // 1. Update purchase_price if provided (including soft-deleted item_units)
        if ($purchasePrice !== null && is_numeric($purchasePrice)) {
            $price = (float) $purchasePrice;
            $updated = DB::table('item_units')
                ->where('is_default', true)
                ->update(['purchase_price' => $price]);

            $this->info("Updated purchase_price to {$price} for {$updated} default item units.");
        }

        // 2. Batch update all sales invoice items with cost = qty * 47.85
        $this->info('Recalculating costs for all sales invoices...');

        $defaultCost = $purchasePrice ? (float) $purchasePrice : 47.85;

        // Update unit_cost from purchase_price lookup, fallback to provided price
        $updated = DB::statement("
            UPDATE sales_invoice_items
            SET unit_cost = COALESCE(
                (
                    SELECT COALESCE(iu.purchase_price, ?)
                    FROM item_units iu
                    WHERE iu.item_id = sales_invoice_items.item_id
                    AND iu.is_default = 1
                    LIMIT 1
                ),
                ?
            ),
            total_cost = ABS(qty) * COALESCE(
                (
                    SELECT COALESCE(iu.purchase_price, ?)
                    FROM item_units iu
                    WHERE iu.item_id = sales_invoice_items.item_id
                    AND iu.is_default = 1
                    LIMIT 1
                ),
                ?
            )
            WHERE deleted_at IS NULL
        ", [$defaultCost, $defaultCost, $defaultCost, $defaultCost]);

        $affected = DB::table('sales_invoice_items')->where('unit_cost', '>', 0)->count();
        $this->info("Updated {$affected} sales invoice items with cost data.");

        // 3. Show profit summary
        $this->newLine();
        $this->info('=== Profit Summary ===');

        $summary = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->where('si.status', '!=', 'cancelled')
            ->whereNull('si.deleted_at')
            ->whereNull('sii.deleted_at')
            ->selectRaw('
                SUM(sii.net_amount) as total_sales,
                SUM(sii.total_cost) as total_cost,
                SUM(sii.net_amount) - SUM(sii.total_cost) as total_profit,
                CASE WHEN SUM(sii.net_amount) > 0
                    THEN ((SUM(sii.net_amount) - SUM(sii.total_cost)) / SUM(sii.net_amount)) * 100
                    ELSE 0
                END as margin
            ')
            ->first();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sales', number_format($summary->total_sales ?? 0, 2)],
                ['Total Cost (COGS)', number_format($summary->total_cost ?? 0, 2)],
                ['Total Profit', number_format($summary->total_profit ?? 0, 2)],
                ['Margin %', number_format($summary->margin ?? 0, 2) . '%'],
            ]
        );

        // 4. Show per-item breakdown
        $this->newLine();
        $this->info('=== Per-Item Cost Summary ===');

        $items = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->join('items as i', 'i.id', '=', 'sii.item_id')
            ->where('si.status', '!=', 'cancelled')
            ->whereNull('si.deleted_at')
            ->whereNull('sii.deleted_at')
            ->select('i.name_ar', 'i.code',
                DB::raw('SUM(sii.qty) as total_qty'),
                DB::raw('SUM(sii.net_amount) as total_sales'),
                DB::raw('SUM(sii.total_cost) as total_cost'),
                DB::raw('SUM(sii.net_amount) - SUM(sii.total_cost) as profit'),
                DB::raw('CASE WHEN SUM(sii.net_amount) > 0 THEN ((SUM(sii.net_amount) - SUM(sii.total_cost)) / SUM(sii.net_amount)) * 100 ELSE 0 END as margin')
            )
            ->groupBy('i.id', 'i.name_ar', 'i.code')
            ->orderByDesc('profit')
            ->get();

        $this->table(
            ['Code', 'Item', 'Qty Sold', 'Sales', 'Cost', 'Profit', 'Margin %'],
            $items->map(fn($row) => [
                $row->code,
                $row->name_ar,
                number_format($row->total_qty, 2),
                number_format($row->total_sales, 2),
                number_format($row->total_cost, 2),
                number_format($row->profit, 2),
                number_format($row->margin, 1) . '%',
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}
