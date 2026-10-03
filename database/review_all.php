<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "  1. ALL CUSTOMERS & BALANCES\n";
echo "========================================\n\n";

$customers = DB::table('customers')
    ->where('is_active', true)
    ->whereNull('deleted_at')
    ->orderBy('id')
    ->get();

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

    $invCount = DB::table('sales_invoices')
        ->where('customer_id', $c->id)
        ->whereNull('deleted_at')
        ->count();

    $collCount = DB::table('collections')
        ->where('customer_id', $c->id)
        ->count();

    $visitCount = DB::table('customer_visits')
        ->where('customer_id', $c->id)
        ->count();

    echo "Customer #{$c->id} | {$c->name_ar}\n";
    echo "  Ledger: debit={$debit} credit={$credit} standalone={$standaloneCredit}\n";
    echo "  Balance: {$balance}\n";
    echo "  Invoices: {$invCount} | Collections: {$collCount} | Visits: {$visitCount}\n";
    echo "  ---\n";
}

echo "\n========================================\n";
echo "  2. ALL CUSTOMER LEDGER ENTRIES\n";
echo "========================================\n\n";

$ledgers = DB::table('customer_ledger')
    ->join('customers', 'customer_ledger.customer_id', '=', 'customers.id')
    ->select('customer_ledger.*', 'customers.name_ar')
    ->orderBy('customer_ledger.customer_id')
    ->orderBy('customer_ledger.id')
    ->get();

foreach ($ledgers as $l) {
    echo "ID:{$l->id} | Cust:#{$l->customer_id}({$l->name_ar}) | {$l->transaction_date} | {$l->reference_type}:{$l->reference_id} | D:{$l->debit} C:{$l->credit} Bal:{$l->balance}\n";
}
if ($ledgers->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  3. ALL SALES INVOICES\n";
echo "========================================\n\n";

$invoices = DB::table('sales_invoices')
    ->leftJoin('customers', 'sales_invoices.customer_id', '=', 'customers.id')
    ->select('sales_invoices.*', 'customers.name_ar')
    ->whereNull('sales_invoices.deleted_at')
    ->orderBy('sales_invoices.id')
    ->get();

foreach ($invoices as $inv) {
    echo "Invoice #{$inv->id} (No:{$inv->invoice_no}) | Cust:#{$inv->customer_id}({$inv->name_ar}) | Date:{$inv->invoice_date}\n";
    echo "  Net:{$inv->net_total} Paid:{$inv->paid_amount} Remain:{$inv->remaining_amount} Status:{$inv->status}\n";
    $items = DB::table('sales_invoice_items')->where('sales_invoice_id', $inv->id)->count();
    echo "  Items: {$items}\n";
    echo "  ---\n";
}
if ($invoices->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  4. ALL COLLECTIONS\n";
echo "========================================\n\n";

$cols = DB::table('collections')
    ->leftJoin('customers', 'collections.customer_id', '=', 'customers.id')
    ->select('collections.*', 'customers.name_ar')
    ->orderBy('collections.id')
    ->get();

foreach ($cols as $c) {
    $linked = $c->sales_invoice_id ? "Invoice:#{$c->sales_invoice_id}" : "STANDALONE";
    echo "Collection #{$c->id} | Cust:#{$c->customer_id}({$c->name_ar}) | {$c->collection_date} | Amt:{$c->amount} | {$linked} | Status:{$c->status}\n";
    if ($c->notes) echo "  Notes: {$c->notes}\n";
}
if ($cols->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  5. SALES INVOICE PAYMENT METHODS\n";
echo "========================================\n\n";

try {
    $pms = DB::table('sales_invoice_payment_methods')
        ->join('sales_invoices', 'sales_invoice_payment_methods.sales_invoice_id', '=', 'sales_invoices.id')
        ->leftJoin('customers', 'sales_invoices.customer_id', '=', 'customers.id')
        ->select('sales_invoice_payment_methods.*', 'customers.name_ar', 'sales_invoices.invoice_no')
        ->orderBy('sales_invoice_payment_methods.sales_invoice_id')
        ->get();

    foreach ($pms as $pm) {
        echo "Invoice:#{$pm->sales_invoice_id}({$pm->invoice_no}) | Cust:({$pm->name_ar}) | Method:{$pm->payment_method_id} | Amt:{$pm->amount}\n";
    }
    if ($pms->isEmpty()) echo "(empty)\n";
} catch (Exception $e) {
    echo "(table not found or empty)\n";
}

echo "\n========================================\n";
echo "  6. CUSTOMER VISITS\n";
echo "========================================\n\n";

$visits = DB::table('customer_visits')
    ->leftJoin('customers', 'customer_visits.customer_id', '=', 'customers.id')
    ->leftJoin('employees', 'customer_visits.employee_id', '=', 'employees.id')
    ->select('customer_visits.*', 'customers.name_ar', 'employees.name as emp_name')
    ->orderBy('customer_visits.id')
    ->get();

foreach ($visits as $v) {
    echo "Visit #{$v->id} | Cust:#{$v->customer_id}({$v->name_ar}) | Emp:{$v->emp_name} | {$v->visit_date} | Status:{$v->visit_status}\n";
}
if ($visits->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  7. PAYMENT METHODS\n";
echo "========================================\n\n";

$methods = DB::table('payment_methods')->where('is_active', true)->get();
foreach ($methods as $m) {
    echo "PM #{$m->id} | Code:{$m->code} | Name:{$m->name_ar}\n";
}
if ($methods->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  8. NUMBER SERIES\n";
echo "========================================\n\n";

$ns = DB::table('number_series')->orderBy('id')->get();
foreach ($ns as $n) {
    echo "NS #{$n->id} | Type:{$n->type} | Next:{$n->next_number} | Prefix:{$n->prefix}\n";
}
if ($ns->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  9. INVENTORY TRANSACTIONS\n";
echo "========================================\n\n";

$inv = DB::table('inventory_transactions')->orderBy('id')->get();
foreach ($inv as $i) {
    echo "InvTx #{$i->id} | Type:{$i->transaction_type} | Date:{$i->transaction_date} | Ref:{$i->reference_type}:{$i->reference_id}\n";
    $items = DB::table('inventory_transaction_items')->where('inventory_transaction_id', $i->id)->get();
    foreach ($items as $it) {
        echo "  Item #{$it->item_id} | Qty:{$it->qty} | UnitCost:{$it->unit_cost}\n";
    }
}
if ($inv->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  10. REP ITEM DISTRIBUTIONS\n";
echo "========================================\n\n";

$dist = DB::table('rep_item_distributions')
    ->leftJoin('employees', 'rep_item_distributions.employee_id', '=', 'employees.id')
    ->select('rep_item_distributions.*', 'employees.name as emp_name')
    ->orderBy('rep_item_distributions.id')
    ->get();

foreach ($dist as $d) {
    echo "Dist #{$d->id} | Emp:{$d->emp_name} | Item:#{$d->item_id} | Issued:{$d->issued_qty} Sold:{$d->sold_qty} Remain:{$d->remaining_qty}\n";
}
if ($dist->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  11. ISSUE ORDERS\n";
echo "========================================\n\n";

$ios = DB::table('issue_orders')->orderBy('id')->get();
foreach ($ios as $io) {
    echo "IO #{$io->id} | Date:{$io->created_at} | Status:{$io->status}\n";
    $items = DB::table('issue_order_items')->where('issue_order_id', $io->id)->get();
    foreach ($items as $it) {
        echo "  Item #{$it->item_id} | Qty:{$it->qty}\n";
    }
}
if ($ios->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  12. LOAD REQUESTS\n";
echo "========================================\n\n";

$lrs = DB::table('load_requests')->orderBy('id')->get();
foreach ($lrs as $lr) {
    echo "LR #{$lr->id} | Date:{$lr->created_at} | Status:{$lr->status}\n";
    $items = DB::table('load_request_items')->where('load_request_id', $lr->id)->get();
    foreach ($items as $it) {
        echo "  Item #{$it->item_id} | Qty:{$it->qty}\n";
    }
}
if ($lrs->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  13. CUSTOMER TYPES\n";
echo "========================================\n\n";

$ct = DB::table('customer_types')->orderBy('id')->get();
foreach ($ct as $c) {
    echo "CT #{$c->id} | {$c->name_ar} | {$c->name_en}\n";
}
if ($ct->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  14. ROUTES\n";
echo "========================================\n\n";

$routes = DB::table('routes')->where('is_active', true)->orderBy('id')->get();
foreach ($routes as $r) {
    echo "Route #{$r->id} | {$r->name_ar}\n";
}
if ($routes->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  15. ROUTE CUSTOMERS\n";
echo "========================================\n\n";

$rcs = DB::table('route_customers')
    ->leftJoin('customers', 'route_customers.customer_id', '=', 'customers.id')
    ->leftJoin('routes', 'route_customers.route_id', '=', 'routes.id')
    ->select('route_customers.*', 'customers.name_ar', 'routes.name_ar as route_name')
    ->orderBy('route_customers.route_id')
    ->orderBy('route_customers.visit_order')
    ->get();

foreach ($rcs as $rc) {
    echo "Route:{$rc->route_name} | Cust:#{$rc->customer_id}({$rc->name_ar}) | Order:{$rc->visit_order}\n";
}
if ($rcs->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  16. AUDIT LOGS\n";
echo "========================================\n\n";

$logs = DB::table('audit_logs')->orderBy('id')->get();
foreach ($logs as $l) {
    echo "Log #{$l->id} | User:#{$l->user_id} | {$l->event} | {$l->auditable_type}:{$l->auditable_id} | {$l->created_at}\n";
}
if ($logs->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  17. PERSONAL ACCESS TOKENS\n";
echo "========================================\n\n";

$tokens = DB::table('personal_access_tokens')->orderBy('id')->get();
foreach ($tokens as $t) {
    echo "Token #{$t->id} | Name:{$t->name} | Tokenable:{$t->tokenable_type}:{$t->tokenable_id} | Created:{$t->created_at}\n";
}
if ($tokens->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  18. EMPLOYEES\n";
echo "========================================\n\n";

$emps = DB::table('employees')->whereNull('deleted_at')->orderBy('id')->get();
foreach ($emps as $e) {
    echo "Emp #{$e->id} | {$e->name} | {$e->email} | {$e->mobile}\n";
}
if ($emps->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  19. BANK ACCOUNTS\n";
echo "========================================\n\n";

$banks = DB::table('bank_accounts')->whereNull('deleted_at')->orderBy('id')->get();
foreach ($banks as $b) {
    echo "Bank #{$b->id} | {$b->name} | Balance:{$b->current_balance}\n";
}
if ($banks->isEmpty()) echo "(empty)\n";

echo "\n========================================\n";
echo "  20. SALES TERRITORIES\n";
echo "========================================\n\n";

$st = DB::table('sales_territories')->orderBy('id')->get();
foreach ($st as $s) {
    echo "ST #{$s->id} | {$s->name_ar}\n";
}
if ($st->isEmpty()) echo "(empty)\n";
