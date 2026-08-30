<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Collection;

$company_id = 2;
$branch_id = 1;
$amount = 1000;
$customerIds = range(11, 30);
$created = 0;

$customers = DB::table('customers')->whereIn('id', $customerIds)->get();

foreach ($customers as $c) {
    Collection::create([
        'company_id' => $company_id,
        'branch_id' => $branch_id,
        'collection_date' => now()->toDateString(),
        'collection_time' => now()->format('H:i:s'),
        'customer_id' => $c->id,
        'amount' => -$amount,
        'notes' => 'إضافة رصيد مبدئي',
        'status' => 'approved',
        'created_by' => null,
    ]);
    $created++;
    echo "Customer {$c->id} ({$c->name_ar}): +{$amount}\n";
}

echo "\nتم اضافة رصيد {$created} عميل بنجاح - كل عميل {$amount} جنيه\n";
