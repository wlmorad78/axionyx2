<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rds = DB::table('rep_daily_settlements')->where('id', 208)->first();
echo "RDS: " . json_encode($rds) . "\n";

$cols = DB::select("PRAGMA table_info(treasury_transactions)");
echo "\ntreasury_transactions columns:\n";
foreach ($cols as $c) echo "  {$c->name}\n";
