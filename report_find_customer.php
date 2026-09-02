<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$term = $argv[1] ?? null;

if (!$term) {
    echo "Usage: php report_find_customer.php <search_term>\n";
    echo "Example: php report_find_customer.php عميل\n";
    exit(1);
}

$customers = DB::table('customers')
    ->where('code', 'like', "%{$term}%")
    ->orWhere('name_ar', 'like', "%{$term}%")
    ->orWhere('name_en', 'like', "%{$term}%")
    ->whereNull('deleted_at')
    ->select('id', 'code', 'name_ar', 'name_en', 'mobile')
    ->limit(20)
    ->get();

if ($customers->isEmpty()) {
    echo "No customers found.\n";
    exit(0);
}

$separator = str_repeat('-', 80);
echo "\n";
echo "=== Customers Found ===\n";
echo $separator . "\n";
printf("%-6s %-12s %-30s %-20s\n", 'ID', 'Code', 'Name AR', 'Mobile');
echo $separator . "\n";

foreach ($customers as $c) {
    printf("%-6s %-12s %-30s %-20s\n",
        $c->id,
        $c->code,
        mb_substr($c->name_ar, 0, 29) ?? '—',
        $c->mobile ?? '—'
    );
}

echo $separator . "\n";
echo "Total: " . $customers->count() . " customers\n\n";
