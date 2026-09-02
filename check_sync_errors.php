<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$logFile = storage_path('logs/laravel.log');

if (!file_exists($logFile)) {
    echo "laravel.log not found\n";
    exit(0);
}

$invoiceNo = $argv[1] ?? null;

echo "=== INVOICE SYNC ERRORS (last 50) ===\n\n";

$lines = file($logFile, FILE_IGNORE_NEW_LINES);
$found = 0;

foreach (array_reverse($lines) as $line) {
    if (strpos($line, 'INVOICE SYNC') === false) continue;

    if ($invoiceNo && strpos($line, $invoiceNo) === false) continue;

    echo trim($line) . "\n";
    $found++;

    if ($found >= 50) break;
}

if ($found === 0) {
    echo "No INVOICE SYNC entries found.\n";
}

echo "\n--- DONE ($found entries) ---\n";
