<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\Treasury\TreasuryController;
use Illuminate\Http\Request;

try {
    $c = new TreasuryController();
    $req = new Request(['per_page' => 5]);
    $r = $c->index($req);
    echo "OK count=" . $r->count() . "\n";
    echo "DONE\n";
} catch (\Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
