<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transfers = DB::table('warehouse_transfers')->get();

if ($transfers->isEmpty()) {
    echo "No warehouse transfers found.\n";
    return;
}

foreach ($transfers as $t) {
    $from = DB::table('warehouses')->where('id', $t->from_warehouse_id)->value('name') ?? $t->from_warehouse_id;
    $to = DB::table('warehouses')->where('id', $t->to_warehouse_id)->value('name') ?? $t->to_warehouse_id;

    echo "=== {$t->transfer_no} ({$t->status}) ===\n";
    echo "  Date: {$t->transfer_date}\n";
    echo "  From: {$from}\n";
    echo "  To: {$to}\n";

    $items = DB::table('warehouse_transfer_items')->where('warehouse_transfer_id', $t->id)->get();
    if ($items->isNotEmpty()) {
        foreach ($items as $i) {
            $itemName = DB::table('items')->where('id', $i->item_id)->value('name_ar') ?? "item_id:{$i->item_id}";
            echo "  - {$itemName}: qty={$i->qty}\n";
        }
    } else {
        echo "  (no items)\n";
    }
    echo "\n";
}
