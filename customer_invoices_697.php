<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customerId = 697;

$customer = DB::table('customers')->where('id', $customerId)->first();
if (!$customer) {
    echo "Customer ID {$customerId} not found.\n";
    return;
}

echo "=== فواتير العميل: {$customer->name} (ID: {$customerId}) ===\n\n";

$invoices = DB::table('sales_invoices')
    ->where('customer_id', $customerId)
    ->orderByDesc('invoice_date')
    ->get();

if ($invoices->isEmpty()) {
    echo "No invoices found.\n";
    return;
}

$totalNet = 0;
$totalPaid = 0;
$totalRemaining = 0;

foreach ($invoices as $inv) {
    $totalNet += $inv->net_total;
    $totalPaid += $inv->paid_amount;
    $totalRemaining += $inv->remaining_amount;

    echo "=== {$inv->invoice_no} | {$inv->invoice_date} | {$inv->status} ===\n";
    echo " _net: " . number_format($inv->net_total, 2) . "\n";
    echo "  Paid: " . number_format($inv->paid_amount, 2) . "\n";
    echo "  Remaining: " . number_format($inv->remaining_amount, 2) . "\n";

    $items = DB::table('sales_invoice_items')->where('sales_invoice_id', $inv->id)->get();
    foreach ($items as $item) {
        $itemName = DB::table('items')->where('id', $item->item_id)->value('name_ar') ?? "item_id:{$item->item_id}";
        echo "  - {$itemName}: qty={$item->qty} | price=" . number_format($item->net_amount, 2) . "\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "Total Invoices: " . $invoices->count() . "\n";
echo "Total Net: " . number_format($totalNet, 2) . "\n";
echo "Total Paid: " . number_format($totalPaid, 2) . "\n";
echo "Total Remaining: " . number_format($totalRemaining, 2) . "\n";
