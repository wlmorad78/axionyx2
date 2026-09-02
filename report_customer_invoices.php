<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$customerId = $argv[1] ?? null;

if (!$customerId) {
    echo "Usage: php report_customer_invoices.php <customer_id>\n";
    echo "Example: php report_customer_invoices.php 697\n";
    exit(1);
}

$invoices = DB::table('sales_invoices')
    ->join('customers', 'sales_invoices.customer_id', '=', 'customers.id')
    ->leftJoin('employees as rep', 'sales_invoices.sales_rep_id', '=', 'rep.user_id')
    ->where('sales_invoices.customer_id', $customerId)
    ->whereNull('sales_invoices.deleted_at')
    ->select(
        'sales_invoices.id',
        'sales_invoices.invoice_no',
        'sales_invoices.invoice_date',
        'sales_invoices.invoice_time',
        'sales_invoices.net_total',
        'sales_invoices.source',
        'sales_invoices.status',
        'customers.name_ar as customer_name',
        'customers.code as customer_code',
        DB::raw("TRIM(CONCAT(rep.first_name_ar, ' ', rep.second_name_ar, ' ', rep.third_name_ar, ' ', rep.last_name_ar)) as rep_name")
    )
    ->orderByDesc('sales_invoices.invoice_date')
    ->orderByDesc('sales_invoices.id')
    ->get();

if ($invoices->isEmpty()) {
    echo "No invoices found for this customer.\n";
    exit(0);
}

$first = $invoices->first();
$separator = str_repeat('-', 110);

echo "\n";
echo "=== Invoices for: " . $first->customer_name . " (" . $first->customer_code . ") ===\n";
echo $separator . "\n";
printf("%-10s %-18s %-12s %-8s %-10s %-12s %12s\n",
    'ID', 'Invoice No', 'Date', 'Time', 'Source', 'Status', 'Net Total');
echo $separator . "\n";

$totalAmount = 0;

foreach ($invoices as $inv) {
    $totalAmount += $inv->net_total;
    $invoiceTime = $inv->invoice_time ?? '—';

    printf("%-10s %-18s %-12s %-8s %-10s %-12s %12s\n",
        $inv->id,
        $inv->invoice_no ?? '—',
        $inv->invoice_date ?? '—',
        $invoiceTime,
        $inv->source ?? '—',
        $inv->status ?? '—',
        number_format($inv->net_total, 2)
    );
}

echo $separator . "\n";
echo "Total Invoices: " . $invoices->count() . " | Total Amount: " . number_format($totalAmount, 2) . "\n\n";
