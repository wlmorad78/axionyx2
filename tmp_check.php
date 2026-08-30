<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo 'user_branches: ' . DB::table('user_branches')->count() . PHP_EOL;
$branches = DB::table('branches')->select('id','name','name_ar','code')->get();
foreach ($branches as $b) echo "  Branch #{$b->id}: {$b->name} ({$b->code})" . PHP_EOL;

$users = DB::table('users')->select('id','name','usercode')->get();
foreach ($users as $u) echo "  User #{$u->id}: {$u->name} ({$u->usercode})" . PHP_EOL;

$ubs = DB::table('user_branches')->get();
foreach ($ubs as $ub) echo "  user_branch: user_id={$ub->user_id} branch_id={$ub->branch_id}" . PHP_EOL;
