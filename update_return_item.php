<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$returnItemId = 78;
$returnOrderId = 27;

DB::table('return_order_items')
    ->where('id', $returnItemId)
    ->update([
        'sold_quantity' => 7620,
        'returned_quantity' => 3380,
        'line_total' => 3380 * 47.95,
    ]);

$totalQty = DB::table('return_order_items')->where('return_order_id', $returnOrderId)->sum('returned_quantity');
$totalAmount = DB::table('return_order_items')->where('return_order_id', $returnOrderId)->sum('line_total');

DB::table('return_orders')
    ->where('id', $returnOrderId)
    ->update([
        'total_quantity' => $totalQty,
        'total_amount' => $totalAmount,
    ]);

echo "Updated!\n";
echo "returned_quantity: 3380\n";
echo "sold_quantity: 7620\n";
echo "line_total: " . number_format(3380 * 47.95, 2) . "\n";
echo "total_qty: " . number_format($totalQty, 2) . "\n";
echo "total_amount: " . number_format($totalAmount, 2) . "\n";
