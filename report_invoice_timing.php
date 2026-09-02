<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$companyId = $argv[1] ?? null;
$limit = $argv[2] ?? 50;

$query = DB::table('sales_invoices')
    ->leftJoin('employees as rep', 'sales_invoices.sales_rep_id', '=', 'rep.user_id')
    ->leftJoin('employees as creator', 'sales_invoices.created_by', '=', 'creator.id')
    ->select(
        'sales_invoices.id',
        'sales_invoices.invoice_no',
        'sales_invoices.invoice_date',
        'sales_invoices.invoice_time',
        'sales_invoices.created_at',
        'sales_invoices.synced_at',
        'sales_invoices.sync_status',
        'sales_invoices.source',
        'sales_invoices.net_total',
        'sales_invoices.status',
        DB::raw("TRIM(CONCAT(rep.first_name_ar, ' ', rep.second_name_ar, ' ', rep.third_name_ar, ' ', rep.last_name_ar)) as rep_name"),
        DB::raw("TRIM(CONCAT(creator.first_name_ar, ' ', creator.second_name_ar, ' ', creator.third_name_ar, ' ', creator.last_name_ar)) as creator_name")
    );

if ($companyId) {
    $query->where('sales_invoices.company_id', $companyId);
}

$invoices = $query->orderByDesc('sales_invoices.invoice_date')
    ->orderByDesc('sales_invoices.id')
    ->limit($limit)
    ->get();

if ($invoices->isEmpty()) {
    echo "No invoices found.\n";
    exit(0);
}

$separator = str_repeat('-', 130);

echo "\n";
echo "=== Invoice Sync Timing Report ===\n";
echo $separator . "\n";
printf("%-8s %-14s %-8s %-8s %-19s %-19s %-10s %-20s %-20s %10s\n",
    'ID', 'Invoice No', 'Date', 'Time', 'Created At', 'Synced At', 'Source', 'Rep', 'Created By', 'Net Total');
echo $separator . "\n";

foreach ($invoices as $inv) {
    $repName = $inv->rep_name ?: '—';
    $creatorName = $inv->creator_name ?: '—';
    $createdAt = $inv->created_at ? date('Y-m-d H:i:s', strtotime($inv->created_at)) : '—';
    $syncedAt = $inv->synced_at ? date('Y-m-d H:i:s', strtotime($inv->synced_at)) : '—';
    $invoiceTime = $inv->invoice_time ?? '—';

    printf("%-8s %-14s %-8s %-8s %-19s %-19s %-10s %-20s %-20s %10s\n",
        $inv->id,
        $inv->invoice_no ?? '—',
        $inv->invoice_date ?? '—',
        $invoiceTime,
        $createdAt,
        $syncedAt,
        $inv->source ?? '—',
        $repName,
        $creatorName,
        number_format($inv->net_total, 2)
    );
}

echo $separator . "\n";
echo "Total: " . $invoices->count() . " invoices\n\n";
