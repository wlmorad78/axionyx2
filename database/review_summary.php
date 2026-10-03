<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "  CUSTOMERS WITH NON-ZERO BALANCES\n";
echo "========================================\n\n";

$customers = DB::table('customers')
    ->where('is_active', true)
    ->whereNull('deleted_at')
    ->orderBy('id')
    ->get();

$nonZero = 0;
$totalDebit = 0;
$totalCredit = 0;
$totalStandalone = 0;

foreach ($customers as $c) {
    $ledger = DB::table('customer_ledger')
        ->where('customer_id', $c->id)
        ->selectRaw('COALESCE(SUM(debit),0) as debit, COALESCE(SUM(credit),0) as credit')
        ->first();

    $standalone = DB::table('collections')
        ->where('customer_id', $c->id)
        ->where('status', 'approved')
        ->whereNull('sales_invoice_id')
        ->selectRaw('COALESCE(SUM(amount),0) as total')
        ->first();

    $debit = (float)($ledger->debit ?? 0);
    $credit = (float)($ledger->credit ?? 0);
    $standaloneCredit = (float)($standalone->total ?? 0);
    $balance = $debit - $credit + $standaloneCredit;

    $totalDebit += $debit;
    $totalCredit += $credit;
    $totalStandalone += $standaloneCredit;

    if (abs($balance) > 0.01) {
        $nonZero++;
        $type = $balance > 0 ? 'DEBT' : 'CREDIT';
        echo "ID:{$c->id} | {$c->name_ar} | Balance:{$balance} ({$type}) | D:{$debit} C:{$credit} S:{$standaloneCredit}\n";
    }
}

echo "\nTotal customers: {$customers->count()}\n";
echo "Non-zero balances: {$nonZero}\n";
echo "Grand totals — Debit:{$totalDebit} Credit:{$totalCredit} Standalone:{$totalStandalone}\n";

echo "\n========================================\n";
echo "  INVOICES SUMMARY\n";
echo "========================================\n\n";

$invStats = DB::table('sales_invoices')
    ->whereNull('deleted_at')
    ->selectRaw('COUNT(*) as cnt, SUM(net_total) as total_net, SUM(paid_amount) as total_paid, SUM(remaining_amount) as total_remain')
    ->first();

echo "Total invoices: {$invStats->cnt}\n";
echo "Total net: " . round($invStats->total_net ?? 0, 2) . "\n";
echo "Total paid: " . round($invStats->total_paid ?? 0, 2) . "\n";
echo "Total remaining: " . round($invStats->total_remain ?? 0, 2) . "\n";

// Invoice date ranges
$invDates = DB::table('sales_invoices')
    ->whereNull('deleted_at')
    ->selectRaw('invoice_date, COUNT(*) as cnt')
    ->groupBy('invoice_date')
    ->orderBy('invoice_date')
    ->get();

echo "\nInvoices by date:\n";
foreach ($invDates as $d) {
    echo "  {$d->invoice_date}: {$d->cnt} invoices\n";
}

echo "\n========================================\n";
echo "  COLLECTIONS SUMMARY\n";
echo "========================================\n\n";

$collStats = DB::table('collections')
    ->selectRaw('COUNT(*) as cnt, SUM(amount) as total_amt')
    ->first();

echo "Total collections: {$collStats->cnt}\n";
echo "Total amount: " . round($collStats->total_amt ?? 0, 2) . "\n";

// Collections with sales_invoice_id vs standalone
$linked = DB::table('collections')->whereNotNull('sales_invoice_id')->count();
$standalone = DB::table('collections')->whereNull('sales_invoice_id')->count();
echo "Linked to invoices: {$linked}\n";
echo "Standalone (no invoice): {$standalone}\n";

$standaloneAmount = DB::table('collections')
    ->whereNull('sales_invoice_id')
    ->where('status', 'approved')
    ->selectRaw('customer_id, SUM(amount) as total')
    ->groupBy('customer_id')
    ->having('total', '!=', 0)
    ->get();

if ($standaloneAmount->isNotEmpty()) {
    echo "\nStandalone collections by customer:\n";
    foreach ($standaloneAmount as $s) {
        $cust = DB::table('customers')->where('id', $s->customer_id)->first();
        $name = $cust ? $cust->name_ar : "Unknown#{$s->customer_id}";
        echo "  #{$s->customer_id}({$name}): {$s->total}\n";
    }
}

echo "\nCollection date ranges:\n";
$collDates = DB::table('collections')
    ->selectRaw('collection_date, COUNT(*) as cnt, SUM(amount) as total')
    ->groupBy('collection_date')
    ->orderBy('collection_date')
    ->get();

foreach ($collDates as $d) {
    echo "  {$d->collection_date}: {$d->cnt} collections, total: " . round($d->total, 2) . "\n";
}

echo "\n========================================\n";
echo "  LEDGER SUMMARY\n";
echo "========================================\n\n";

$ledgerStats = DB::table('customer_ledger')
    ->selectRaw('COUNT(*) as cnt, SUM(debit) as total_debit, SUM(credit) as total_credit')
    ->first();

echo "Total ledger entries: {$ledgerStats->cnt}\n";
echo "Total debit: " . round($ledgerStats->total_debit ?? 0, 2) . "\n";
echo "Total credit: " . round($ledgerStats->total_credit ?? 0, 2) . "\n";

$ledgerDates = DB::table('customer_ledger')
    ->selectRaw('transaction_date, COUNT(*) as cnt')
    ->groupBy('transaction_date')
    ->orderBy('transaction_date')
    ->get();

echo "\nLedger by date:\n";
foreach ($ledgerDates as $d) {
    echo "  {$d->transaction_date}: {$d->cnt} entries\n";
}

echo "\n========================================\n";
echo "  VISITS SUMMARY\n";
echo "========================================\n\n";

$visitStats = DB::table('customer_visits')
    ->selectRaw('COUNT(*) as cnt')
    ->first();
echo "Total visits: {$visitStats->cnt}\n";

$visitDates = DB::table('customer_visits')
    ->selectRaw('visit_date, COUNT(*) as cnt')
    ->groupBy('visit_date')
    ->orderBy('visit_date')
    ->get();

echo "Visits by date:\n";
foreach ($visitDates as $d) {
    echo "  {$d->visit_date}: {$d->cnt} visits\n";
}

echo "\n========================================\n";
echo "  INVENTORY / ISSUE ORDERS / LOAD REQUESTS\n";
echo "========================================\n\n";

$invTx = DB::table('inventory_transactions')->count();
echo "Inventory transactions: {$invTx}\n";

$issueOrders = DB::table('issue_orders')->count();
echo "Issue orders: {$issueOrders}\n";

$loadReqs = DB::table('load_requests')->count();
echo "Load requests: {$loadReqs}\n";

$dist = DB::table('rep_item_distributions')
    ->selectRaw('SUM(issued_qty) as issued, SUM(sold_qty) as sold, SUM(remaining_qty) as remaining')
    ->first();
echo "Rep distributions — Issued:{$dist->issued} Sold:{$dist->sold} Remaining:{$dist->remaining}\n";

echo "\n========================================\n";
echo "  TOKENS & AUDIT\n";
echo "========================================\n\n";

$tokens = DB::table('personal_access_tokens')->count();
echo "Active tokens: {$tokens}\n";

$auditLogs = DB::table('audit_logs')->count();
echo "Audit logs: {$auditLogs}\n";

echo "\n========================================\n";
echo "  NUMBER SERIES\n";
echo "========================================\n\n";

$ns = DB::table('number_series')->orderBy('id')->get();
foreach ($ns as $n) {
    echo "Type:{$n->type} | Next:{$n->next_number} | Prefix:{$n->prefix}\n";
}
if ($ns->isEmpty()) echo "(empty)\n";
