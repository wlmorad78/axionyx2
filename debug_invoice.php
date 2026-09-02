<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$invoiceNo = $argv[1] ?? '260901-2025-001';

echo "=== Debug Invoice: $invoiceNo ===\n\n";

// 1. Find the invoice
$inv = DB::table('sales_invoices')
    ->where('invoice_no', $invoiceNo)
    ->first();

if (!$inv) {
    echo "❌ Invoice NOT FOUND in sales_invoices!\n";
    echo "Checking temp_invoice_no...\n";
    $inv = DB::table('sales_invoices')
        ->where('temp_invoice_no', $invoiceNo)
        ->first();
    if (!$inv) {
        echo "❌ Also not found by temp_invoice_no.\n";
        echo "\nChecking if it exists in any table...\n";
        $any = DB::select("SELECT id, invoice_no, temp_invoice_no FROM sales_invoices WHERE invoice_no LIKE ? OR temp_invoice_no LIKE ?", ["%$invoiceNo%", "%$invoiceNo%"]);
        if (empty($any)) {
            echo "❌ Invoice does NOT exist in database at all.\n";
        } else {
            foreach ($any as $row) {
                echo "  Found: id={$row->id} invoice_no={$row->invoice_no} temp={$row->temp_invoice_no}\n";
            }
        }
        exit(0);
    }
}

echo "✅ Invoice Found:\n";
echo "   ID:              {$inv->id}\n";
echo "   Invoice No:      {$inv->invoice_no}\n";
echo "   Temp Invoice No: {$inv->temp_invoice_no}\n";
echo "   Invoice Date:    {$inv->invoice_date}\n";
echo "   Invoice Time:    {$inv->invoice_time}\n";
echo "   Created At:      {$inv->created_at}\n";
echo "   Synced At:       " . ($inv->synced_at ?: 'NULL - NOT SYNCED') . "\n";
echo "   Sync Status:     {$inv->sync_status}\n";
echo "   Source:          {$inv->source}\n";
echo "   Mode:            {$inv->mode}\n";
echo "   Status:          {$inv->status}\n";
echo "   Client UUID:     {$inv->client_uuid}\n";
echo "   Sales Rep ID:    {$inv->sales_rep_id}\n";
echo "   Net Total:       {$inv->net_total}\n";

// 2. Check sync_logs for this invoice
echo "\n--- Sync Logs ---\n";
$logs = DB::table('sync_logs')
    ->where('table_name', 'sales_invoices')
    ->where('record_id', $inv->id)
    ->get();

if ($logs->isEmpty()) {
    echo "No sync_logs found for this invoice.\n";
} else {
    foreach ($logs as $log) {
        echo "  Batch: {$log->sync_batch_id} | Op: {$log->operation} | Status: {$log->status} | Created: {$log->created_at}\n";
    }
}

// 3. Check Laravel log for INVOICE SYNC errors
echo "\n--- Laravel Log (INVOICE SYNC errors) ---\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $lines = explode("\n", $content);
    $found = 0;
    foreach (array_reverse($lines) as $line) {
        if (strpos($line, 'INVOICE SYNC') !== false) {
            echo "  " . trim($line) . "\n";
            $found++;
            if ($found >= 20) break;
        }
    }
    if ($found === 0) {
        echo "  No INVOICE SYNC entries found.\n";
    } else {
        echo "  (showing last $found entries)\n";
    }
} else {
    echo "  laravel.log not found.\n";
}

// 4. Check if client_uuid already exists (duplicate detection)
if ($inv->client_uuid) {
    echo "\n--- Duplicate Check (client_uuid: {$inv->client_uuid}) ---\n";
    $dupes = DB::table('sales_invoices')
        ->where('client_uuid', $inv->client_uuid)
        ->get();
    echo "  Total records with same client_uuid: " . $dupes->count() . "\n";
    foreach ($dupes as $d) {
        echo "  - ID: {$d->id} | No: {$d->invoice_no} | Status: {$d->status} | Synced: {$d->synced_at}\n";
    }
}
