<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$purchasePrice = 47.85;

$updated = DB::table('sales_invoice_items')
    ->where(function ($q) {
        $q->where('unit_cost', 0)->orWhereNull('unit_cost');
    })
    ->update([
        'unit_cost' => $purchasePrice,
        'total_cost' => DB::raw("qty * $purchasePrice"),
    ]);

echo "Updated $updated items with purchase price $purchasePrice\n";

$summary = DB::table('sales_invoice_items')
    ->select('sales_invoice_id')
    ->selectRaw('SUM(net_amount) as total_net')
    ->selectRaw('SUM(unit_cost * qty) as total_cost')
    ->selectRaw('SUM(net_amount - unit_cost * qty) as profit')
    ->groupBy('sales_invoice_id')
    ->get();

echo "\n=== Invoice Profit Summary ===\n";
$grandRevenue = 0;
$grandCost = 0;
$grandProfit = 0;
foreach ($summary as $row) {
    echo "Invoice #{$row->sales_invoice_id}: Revenue={$row->total_net} | Cost={$row->total_cost} | Profit={$row->profit}\n";
    $grandRevenue += $row->total_net;
    $grandCost += $row->total_cost;
    $grandProfit += $row->profit;
}
echo "\nGrand Total: Revenue=$grandRevenue | Cost=$grandCost | Profit=$grandProfit\n";
