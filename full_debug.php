<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$invoiceNo = $argv[1] ?? '260901-2025-001';

echo "=== FULL DEBUG: $invoiceNo ===\n\n";

// 1. Search in sales_invoices
echo "--- 1. sales_invoices ---\n";
$inv = DB::table('sales_invoices')
    ->where('invoice_no', $invoiceNo)
    ->orWhere('temp_invoice_no', $invoiceNo)
    ->first();

if ($inv) {
    echo "FOUND: id={$inv->id} | no={$inv->invoice_no} | temp={$inv->temp_invoice_no} | synced_at={$inv->synced_at} | sync_status={$inv->sync_status} | source={$inv->source} | status={$inv->status}\n";
} else {
    echo "NOT FOUND\n";
}

// 2. Search in sync_logs
echo "\n--- 2. sync_logs (all) ---\n";
$logsCount = DB::table('sync_logs')->count();
echo "Total sync_logs records: $logsCount\n";

// 3. Search in sync_batches
echo "\n--- 3. sync_batches (last 10) ---\n";
$batches = DB::table('sync_batches')->orderByDesc('id')->limit(10)->get();
foreach ($batches as $b) {
    echo "  Batch {$b->id} | device={$b->device_id} | rep={$b->sales_rep_id} | start={$b->sync_start} | end={$b->sync_end} | status={$b->status}\n";
}

// 4. Search Laravel log for ANY errors (not just INVOICE SYNC)
echo "\n--- 4. Laravel log (last 30 errors) ---\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    $found = 0;
    foreach (array_reverse($lines) as $line) {
        if (strpos($line, '"level":40') !== false || strpos($line, 'ERROR') !== false || strpos($line, 'error') !== false) {
            echo "  " . substr(trim($line), 0, 200) . "\n";
            $found++;
            if ($found >= 30) break;
        }
    }
    echo "  ($found error entries)\n";
}

// 5. Check temp_invoice_no pattern
echo "\n--- 5. Invoices with similar pattern ---\n";
$similar = DB::table('sales_invoices')
    ->where('invoice_no', 'LIKE', '260901-2025%')
    ->orWhere('temp_invoice_no', 'LIKE', '260901-2025%')
    ->get(['id', 'invoice_no', 'temp_invoice_no', 'synced_at', 'source', 'status']);

if ($similar->isEmpty()) {
    echo "  No invoices with pattern 260901-2025%\n";
} else {
    foreach ($similar as $s) {
        echo "  id={$s->id} | no={$s->invoice_no} | temp={$s->temp_invoice_no} | synced={$s->synced_at} | source={$s->source} | status={$s->status}\n";
    }
}

echo "\n=== DONE ===\n";
