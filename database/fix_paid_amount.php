<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVOICES BY SOURCE ===\n\n";
$r = DB::select('SELECT source, COUNT(*) as cnt, SUM(paid_amount) as paid, SUM(net_total) as net FROM sales_invoices WHERE deleted_at IS NULL GROUP BY source');
foreach ($r as $x) echo "  " . ($x->source ?? 'NULL') . ": cnt={$x->cnt} paid=" . round($x->paid,2) . " net=" . round($x->net,2) . "\n";

echo "\n=== UNPAID INVOICE #1356 ===\n\n";
$inv = DB::table('sales_invoices')->where('id', 1356)->first();
echo json_encode($inv, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

echo "\n=== SAMPLE: How does handheld sync set paid_amount? ===\n";
echo "Looking at 10 invoices with source='handheld' that DO have collections...\n\n";

$samples = DB::select('
    SELECT si.id, si.customer_id, si.net_total, si.paid_amount, si.remaining_amount, si.source, si.invoice_date,
           SUM(c.amount) as coll_total
    FROM sales_invoices si
    INNER JOIN collections c ON c.sales_invoice_id = si.id AND c.status = "approved"
    WHERE si.deleted_at IS NULL
    AND si.source = "handheld"
    GROUP BY si.id
    LIMIT 10
');
foreach ($samples as $s) {
    echo "Inv #{$s->id} | Net:{$s->net_total} | Paid:{$s->paid_amount} | Coll:{$s->coll_total} | Remain:{$s->remaining_amount}\n";
}

echo "\n=== COLLECTIONS: METHOD_ID 2 = ? ===\n\n";
// What is payment_method_id=2?
$pm = DB::table('payment_methods')->get();
foreach ($pm as $p) echo "  ID:{$p->id} Name:{$p->name}\n";
if ($pm->isEmpty()) echo "  (payment_methods table empty or missing)\n";
