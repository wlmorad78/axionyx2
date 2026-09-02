<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$repId = $argv[1] ?? null;
$date = $argv[2] ?? null;
$itemId = $argv[3] ?? null;

if (!$repId || !$date || !$itemId) {
    echo "Usage: php report_rep_sales_product.php <rep_id> <date> <item_id>\n";
    echo "Example: php report_rep_sales_product.php 2025 2026-09-01 2\n";
    exit(1);
}

$query = DB::table('sales_invoices')
    ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
    ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
    ->join('customers', 'sales_invoices.customer_id', '=', 'customers.id')
    ->leftJoin('employees as rep', 'sales_invoices.sales_rep_id', '=', 'rep.user_id')
    ->leftJoin('employees as creator', 'sales_invoices.created_by', '=', 'creator.id')
    ->where('sales_invoices.sales_rep_id', $repId)
    ->where('sales_invoices.invoice_date', $date)
    ->where('sales_invoice_items.item_id', $itemId)
    ->whereNull('sales_invoices.deleted_at')
    ->whereNull('sales_invoice_items.deleted_at')
    ->select(
        'sales_invoices.id as invoice_id',
        'sales_invoices.invoice_no',
        'sales_invoices.invoice_date',
        'sales_invoices.invoice_time',
        'customers.name_ar as customer_name',
        'customers.code as customer_code',
        'items.name_ar as item_name',
        'items.code as item_code',
        'sales_invoice_items.qty',
        'sales_invoice_items.bonus_qty',
        'sales_invoice_items.price',
        'sales_invoice_items.net_amount',
        'sales_invoices.source',
        'sales_invoices.status',
        DB::raw("TRIM(CONCAT(rep.first_name_ar, ' ', rep.second_name_ar, ' ', rep.third_name_ar, ' ', rep.last_name_ar)) as rep_name"),
        DB::raw("TRIM(CONCAT(creator.first_name_ar, ' ', creator.second_name_ar, ' ', creator.third_name_ar, ' ', creator.last_name_ar)) as creator_name")
    );

$items = $query->orderBy('sales_invoices.invoice_date')
    ->orderBy('sales_invoices.id')
    ->get();

if ($items->isEmpty()) {
    echo "No sales found for this rep on this date with this product.\n";
    exit(0);
}

$separator = str_repeat('-', 140);

echo "\n";
echo "=== Sales Report for Rep: " . $items->first()->rep_name . " ===\n";
echo "Date: " . $date . " | Product: " . $items->first()->item_name . " (" . $items->first()->item_code . ")\n";
echo $separator . "\n";
printf("%-10s %-16s %-8s %-8s %-25s %-10s %-8s %-6s %-8s %-12s %-10s\n",
    'Invoice', 'Customer Code', 'Time', 'Qty', 'Customer Name', 'Item', 'Bonus', 'Price', 'Total', 'Status', 'Source');
echo $separator . "\n";

$totalQty = 0;
$totalBonus = 0;
$totalAmount = 0;

foreach ($items as $item) {
    $totalQty += $item->qty;
    $totalBonus += $item->bonus_qty;
    $totalAmount += $item->net_amount;

    printf("%-10s %-16s %-8s %-8s %-25s %-10s %-8s %-6s %-8s %-12s %-10s\n",
        $item->invoice_no ?? '—',
        $item->customer_code ?? '—',
        $item->invoice_time ?? '—',
        number_format($item->qty, 2),
        mb_substr($item->customer_name, 0, 24) ?? '—',
        mb_substr($item->item_name, 0, 9) ?? '—',
        number_format($item->bonus_qty, 2),
        number_format($item->price, 2),
        number_format($item->net_amount, 2),
        $item->status ?? '—',
        $item->source ?? '—'
    );
}

echo $separator . "\n";
echo "Total Qty: " . number_format($totalQty, 2) . " | Total Bonus: " . number_format($totalBonus, 2) . " | Total Amount: " . number_format($totalAmount, 2) . "\n";
echo "Total Invoices: " . $items->count() . "\n\n";
