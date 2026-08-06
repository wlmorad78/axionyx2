<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@axionyx.com')->first();
if (! $user) {
    echo "Admin user not found\n";
    exit(1);
}

$token = $user->createToken('test')->plainTextToken;
echo "Token created: " . substr($token, 0, 20) . "...\n";
echo "User: {$user->name}\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
$user->tokens()->delete();
echo "Auth test OK\n";
