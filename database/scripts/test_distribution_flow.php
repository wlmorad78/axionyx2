<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\DispatchOrder;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\RepReturnOrder;
use App\Models\Representative;
use App\Models\RepresentativeStock;
use App\Models\Region;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Invoice;
use App\Services\StockService;

$stockService = app(StockService::class);

$user = User::firstOrCreate(
    ['email' => 'rep@test.com'],
    ['name' => 'Rep User', 'password' => 'password', 'is_active' => true]
);

$region = Region::firstOrCreate(['name' => 'Alexandria'], ['parent_id' => null]);
$rep = Representative::firstOrCreate(
    ['user_id' => $user->id],
    ['region_id' => $region->id, 'target_amount' => 0, 'commission_rate' => 0]
);

$warehouse = Warehouse::firstOrCreate(['name' => 'Main Warehouse'], ['address' => 'HQ']);
$product = Product::firstOrCreate(
    ['code' => 'P-TEST'],
    ['name' => 'Test Product', 'sale_price' => 10, 'purchase_price' => 5]
);

ProductStock::updateOrCreate(
    ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
    ['quantity' => 100]
);

$dispatch = DispatchOrder::create([
    'order_no' => 'DO-TEST-' . time(),
    'warehouse_id' => $warehouse->id,
    'representative_id' => $rep->id,
    'order_date' => now(),
    'status' => 'pending',
]);
$dispatch->items()->create(['product_id' => $product->id, 'quantity' => 20, 'unit_cost' => 5]);
$stockService->approveDispatchOrder($dispatch->fresh('items'));

$repQty = RepresentativeStock::where('representative_id', $rep->id)->value('quantity');
echo "After dispatch - Rep stock: {$repQty}\n";

$customer = Customer::firstOrCreate(
    ['code' => 'C-TEST'],
    ['name' => 'Test Customer']
);

$invoice = Invoice::create([
    'invoice_no' => 'INV-TEST-' . time(),
    'customer_id' => $customer->id,
    'representative_id' => $rep->id,
    'invoice_date' => now(),
    'subtotal' => 50,
    'total' => 50,
    'status' => 'unpaid',
    'remaining_amount' => 50,
]);
$invoice->items()->create(['product_id' => $product->id, 'quantity' => 5, 'price' => 10, 'total' => 50]);
$stockService->deductForInvoiceSale($invoice->fresh('items'));

$repQty = RepresentativeStock::where('representative_id', $rep->id)->value('quantity');
echo "After sale - Rep stock: {$repQty}\n";

$return = RepReturnOrder::create([
    'order_no' => 'RR-TEST-' . time(),
    'warehouse_id' => $warehouse->id,
    'representative_id' => $rep->id,
    'return_date' => now(),
    'status' => 'pending',
]);
$return->items()->create(['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5]);
$stockService->approveRepReturnOrder($return->fresh('items'));

$whQty = ProductStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('quantity');
$repQty = RepresentativeStock::where('representative_id', $rep->id)->value('quantity');
echo "After return - Warehouse: {$whQty}, Rep: {$repQty}\n";
echo "Flow test completed successfully.\n";
