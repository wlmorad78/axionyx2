<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%load%' OR name LIKE '%issue%' OR name LIKE '%return%' OR name LIKE '%invoice%' OR name LIKE '%route%') ORDER BY name");
foreach($tables as $t) echo $t->name . "\n";
