<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$repId = $argv[1] ?? null;
$dateFrom = $argv[2] ?? null;
$dateTo = $argv[3] ?? null;

if (!$repId) {
    echo "Usage: php report_rep_settlements.php <rep_id> [date_from] [date_to]\n";
    echo "Example: php report_rep_settlements.php 2025 2026-09-01 2026-09-01\n";
    exit(1);
}

$query = DB::table('rep_daily_settlements')
    ->leftJoin('employees as rep', 'rep_daily_settlements.sales_rep_id', '=', 'rep.id')
    ->leftJoin('employees as creator', 'rep_daily_settlements.created_by', '=', 'creator.id')
    ->where('rep_daily_settlements.sales_rep_id', $repId)
    ->whereNull('rep_daily_settlements.deleted_at');

if ($dateFrom) {
    $query->where('rep_daily_settlements.settlement_date', '>=', $dateFrom);
}
if ($dateTo) {
    $query->where('rep_daily_settlements.settlement_date', '<=', $dateTo);
}

$settlements = $query->select(
    'rep_daily_settlements.id',
    'rep_daily_settlements.settlement_no',
    'rep_daily_settlements.settlement_date',
    'rep_daily_settlements.total_sales_value',
    'rep_daily_settlements.total_collections_value',
    'rep_daily_settlements.total_expenses',
    'rep_daily_settlements.total_from_balance',
    'rep_daily_settlements.expected_cash',
    'rep_daily_settlements.actual_cash',
    'rep_daily_settlements.cash_difference',
    'rep_daily_settlements.shortage',
    'rep_daily_settlements.status',
    'rep_daily_settlements.notes',
    DB::raw("TRIM(CONCAT(rep.first_name_ar, ' ', rep.second_name_ar, ' ', rep.third_name_ar, ' ', rep.last_name_ar)) as rep_name"),
    DB::raw("TRIM(CONCAT(creator.first_name_ar, ' ', creator.second_name_ar, ' ', creator.third_name_ar, ' ', creator.last_name_ar)) as creator_name")
)
->orderByDesc('rep_daily_settlements.settlement_date')
->orderByDesc('rep_daily_settlements.id')
->get();

if ($settlements->isEmpty()) {
    echo "No settlements found.\n";
    exit(0);
}

$first = $settlements->first();
$separator = str_repeat('-', 120);

echo "\n";
echo "=== Rep Settlements Report: " . $first->rep_name . " ===\n";
echo $separator . "\n";
printf("%-8s %-10s %-12s %-14s %-14s %-12s %-14s %-14s %-12s %-10s\n",
    'ID', 'No', 'Date', 'Sales', 'Collections', 'Expenses', 'Expected', 'Actual', 'Diff', 'Status');
echo $separator . "\n";

$totalSales = 0;
$totalCollections = 0;
$totalExpenses = 0;
$totalExpected = 0;
$totalActual = 0;

foreach ($settlements as $s) {
    $totalSales += $s->total_sales_value;
    $totalCollections += $s->total_collections_value;
    $totalExpenses += $s->total_expenses;
    $totalExpected += $s->expected_cash;
    $totalActual += $s->actual_cash;

    printf("%-8s %-10s %-12s %-14s %-14s %-12s %-14s %-14s %-12s %-10s\n",
        $s->id,
        $s->settlement_no ?? '—',
        $s->settlement_date ?? '—',
        number_format($s->total_sales_value, 2),
        number_format($s->total_collections_value, 2),
        number_format($s->total_expenses, 2),
        number_format($s->expected_cash, 2),
        number_format($s->actual_cash, 2),
        number_format($s->cash_difference, 2),
        $s->status ?? '—'
    );
}

echo $separator . "\n";
echo "Total Sales: " . number_format($totalSales, 2) . "\n";
echo "Total Collections: " . number_format($totalCollections, 2) . "\n";
echo "Total Expenses: " . number_format($totalExpenses, 2) . "\n";
echo "Total Expected: " . number_format($totalExpected, 2) . "\n";
echo "Total Actual: " . number_format($totalActual, 2) . "\n";
echo "Total Settlements: " . $settlements->count() . "\n\n";
