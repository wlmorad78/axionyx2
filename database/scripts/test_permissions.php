<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\AuthorizationService;

$authz = app(AuthorizationService::class);

$rep = User::where('email', 'rep@axionyx.com')->first()->load('roles', 'representative');
$warehouse = User::where('email', 'warehouse@axionyx.com')->first()->load('roles');
$admin = User::where('email', 'admin@axionyx.com')->first()->load('roles');

assert($authz->can($rep, 'invoices', 'store'), 'Rep should create invoices');
assert(! $authz->can($rep, 'dispatch-orders', 'approve'), 'Rep cannot approve dispatch');
assert($authz->can($warehouse, 'dispatch-orders', 'approve'), 'Warehouse can approve dispatch');
assert(! $authz->can($warehouse, 'users', 'index'), 'Warehouse cannot list users');
assert($authz->can($admin, 'users', 'index'), 'Admin can list users');

echo "Permission tests passed.\n";
