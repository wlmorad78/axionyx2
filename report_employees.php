<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$employees = DB::table('employees')
    ->whereNull('deleted_at')
    ->select(
        'employees.id',
        'employees.user_id',
        'employees.employee_code',
        DB::raw("TRIM(CONCAT(employees.first_name_ar, ' ', employees.second_name_ar, ' ', employees.third_name_ar, ' ', employees.last_name_ar)) as full_name")
    )
    ->orderBy('employees.id')
    ->get();

$separator = str_repeat('-', 60);
echo "\n";
echo "=== Employees List ===\n";
echo $separator . "\n";
printf("%-6s %-10s %-12s %-30s\n", 'ID', 'User ID', 'Code', 'Name');
echo $separator . "\n";

foreach ($employees as $e) {
    printf("%-6s %-10s %-12s %-30s\n",
        $e->id,
        $e->user_id ?? '—',
        $e->employee_code ?? '—',
        $e->full_name ?? '—'
    );
}

echo $separator . "\n";
echo "Total: " . $employees->count() . " employees\n\n";

$settlementCount = DB::table('rep_daily_settlements')->whereNull('deleted_at')->count();
echo "Total settlements in DB: " . $settlementCount . "\n\n";

if ($settlementCount > 0) {
    $samples = DB::table('rep_daily_settlements')
        ->whereNull('deleted_at')
        ->select('id', 'settlement_no', 'sales_rep_id', 'settlement_date', 'status')
        ->orderByDesc('id')
        ->limit(5)
        ->get();

    echo "=== Sample Settlements ===\n";
    echo $separator . "\n";
    printf("%-6s %-12s %-12s %-12s %-10s\n", 'ID', 'No', 'Rep ID', 'Date', 'Status');
    echo $separator . "\n";

    foreach ($samples as $s) {
        printf("%-6s %-12s %-12s %-12s %-10s\n",
            $s->id,
            $s->settlement_no,
            $s->sales_rep_id,
            $s->settlement_date,
            $s->status
        );
    }
    echo $separator . "\n";
}
