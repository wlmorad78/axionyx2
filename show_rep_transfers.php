<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transfers = DB::table('representative_transfers')->orderByDesc('id')->get();

if ($transfers->isEmpty()) {
    echo "No representative transfers found.\n";
    return;
}

foreach ($transfers as $t) {
    $fromId = $t->from_employee_id ?? $t->from_user_id ?? null;
    $toId = $t->to_employee_id ?? $t->to_user_id ?? null;

    $fromName = $fromId ? (DB::table('employees')->where('id', $fromId)->value('name') ?? DB::table('users')->where('id', $fromId)->value('name') ?? "ID:{$fromId}") : '-';
    $toName = $toId ? (DB::table('employees')->where('id', $toId)->value('name') ?? DB::table('users')->where('id', $toId)->value('name') ?? "ID:{$toId}") : '-';

    echo "=== ID:{$t->id} | {$t->status} ===\n";
    echo "  From: {$fromName}\n";
    echo "  To: {$toName}\n";
    echo "  Date: {$t->created_at}\n";

    $items = DB::table('representative_transfer_items')->where('representative_transfer_id', $t->id)->get();
    if ($items->isNotEmpty()) {
        foreach ($items as $i) {
            $itemName = DB::table('items')->where('id', $i->item_id)->value('name_ar') ?? "item_id:{$i->item_id}";
            echo "  - {$itemName}: qty={$i->quantity}\n";
        }
    } else {
        echo "  (no items)\n";
    }
    echo "\n";
}
