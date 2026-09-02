<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$txns = DB::table('treasury_transactions')->where('company_id', 2)->get();
echo "Count: " . $txns->count() . "\n";
foreach ($txns as $t) {
    echo json_encode($t) . "\n";
}
