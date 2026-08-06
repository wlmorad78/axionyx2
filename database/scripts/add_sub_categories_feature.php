<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure accounting feature is in Starter plan
$starter = \App\Models\SubscriptionPlan::where('code', 'starter')->first();
$hasAccounting = $starter->features()->where('features.code', 'accounting')->exists();
if (!$hasAccounting) {
    $f = \App\Models\Feature::where('code', 'accounting')->first();
    if ($f) {
        $starter->features()->attach($f->id, ['is_enabled' => true]);
        echo "Added accounting to Starter" . PHP_EOL;
    } else {
        echo "accounting feature not found" . PHP_EOL;
    }
} else {
    echo "Starter already has accounting" . PHP_EOL;
}

echo "Starter features: " . implode(', ', $starter->fresh()->features()->pluck('features.code')->toArray()) . PHP_EOL;
