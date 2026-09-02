<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$invoiceNo = $argv[1] ?? null;

if (!$invoiceNo) {
    echo "Usage: php report_invoice_items.php <invoice_no>\n";
    echo "Example: php report_invoice_items.php 260901-2025-026\n";
    exit(1);
}

$items = DB::table('sales_invoices')
    ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
    ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
    ->leftJoin('customers', 'sales_invoices.customer_id', '=', 'customers.id')
    ->leftJoin('employees as rep', 'sales_invoices.sales_rep_id', '=', 'rep.user_id')
    ->leftJoin('units', 'sales_invoice_items.unit_id', '=', 'units.id')
    ->where('sales_invoices.invoice_no', $invoiceNo)
    ->whereNull('sales_invoices.deleted_at')
    ->whereNull('sales_invoice_items.deleted_at')
    ->select(
        'sales_invoices.invoice_no',
        'sales_invoices.invoice_date',
        'sales_invoices.invoice_time',
        'sales_invoices.net_total',
        'customers.name_ar as customer_name',
        'customers.code as customer_code',
        DB::raw("TRIM(CONCAT(rep.first_name_ar, ' ', rep.second_name_ar, ' ', rep.third_name_ar, ' ', rep.last_name_ar)) as rep_name"),
        'items.code as item_code',
        'items.name_ar as item_name',
        'units.name_ar as unit_name',
        'sales_invoice_items.qty',
        'sales_invoice_items.bonus_qty',
        'sales_invoice_items.price',
        'sales_invoice_items.discount_amount',
        'sales_invoice_items.tax_amount',
        'sales_invoice_items.net_amount'
    )
    ->orderBy('sales_invoice_items.id')
    ->get();

if ($items->isEmpty()) {
    echo "Invoice not found.\n";
    exit(0);
}

$first = $items->first();
$separator = str_repeat('-', 120);

echo "\n";
echo "=== Invoice Items: " . $first->invoice_no . " ===\n";
echo "Date: " . $first->invoice_date . " | Time: " . $first->invoice_time . "\n";
echo "Customer: " . $first->customer_name . " (" . $first->customer_code . ")\n";
echo "Rep: " . $first->rep_name . "\n";
echo $separator . "\n";
printf("%-10s %-25s %-10s %-8s %-8s %-10s %-10s %-10s %-12s\n",
    'Code', 'Item Name', 'Unit', 'Qty', 'Bonus', 'Price', 'Disc', 'Tax', 'Net Amount');
echo $separator . "\n";

$totalQty = 0;
$totalBonus = 0;
$totalAmount = 0;

foreach ($items as $item) {
    $totalQty += $item->qty;
    $totalBonus += $item->bonus_qty;
    $totalAmount += $item->net_amount;

    printf("%-10s %-25s %-10s %-8s %-8s %-10s %-10s %-10s %-12s\n",
        $item->item_code ?? '—',
        mb_substr($item->item_name, 0, 24) ?? '—',
        $item->unit_name ?? '—',
        number_format($item->qty, 2),
        number_format($item->bonus_qty, 2),
        number_format($item->price, 2),
        number_format($item->discount_amount, 2),
        number_format($item->tax_amount, 2),
        number_format($item->net_amount, 2)
    );
}

echo $separator . "\n";
echo "Total Qty: " . number_format($totalQty, 2) . " | Total Bonus: " . number_format($totalBonus, 2) . " | Total Amount: " . number_format($totalAmount, 2) . "\n\n";
