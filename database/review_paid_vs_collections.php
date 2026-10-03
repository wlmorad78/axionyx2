<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVOICES WITHOUT MATCHING COLLECTIONS ===\n\n";

$invWithoutColl = DB::select('
    SELECT si.id, si.customer_id, si.net_total, si.paid_amount, si.remaining_amount, si.invoice_date, si.created_at
    FROM sales_invoices si
    LEFT JOIN (
        SELECT sales_invoice_id, SUM(amount) as coll_total
        FROM collections
        WHERE status = ?
        GROUP BY sales_invoice_id
    ) c ON c.sales_invoice_id = si.id
    WHERE si.deleted_at IS NULL
    AND (c.coll_total IS NULL OR ABS(si.paid_amount - c.coll_total) > 0.01)
    ORDER BY si.id
', ['approved']);

echo "Count: " . count($invWithoutColl) . "\n\n";

$withLedger = 0;
$withoutLedger = 0;
$noPayment = 0;

foreach (array_slice($invWithoutColl, 0, 15) as $inv) {
    $ledger = DB::table('customer_ledger')
        ->where('reference_type', 'sales_invoice')
        ->where('reference_id', $inv->id)
        ->first();

    $ledgerInfo = $ledger ? "D:{$ledger->debit} C:{$ledger->credit}" : "NO LEDGER";
    echo "Inv #{$inv->id} | Cust:{$inv->customer_id} | Net:{$inv->net_total} | Paid:{$inv->paid_amount} | Remain:{$inv->remaining_amount} | Ledger:{$ledgerInfo}\n";
    
    if ($ledger) $withLedger++;
    else $withoutLedger++;
    if ($inv->paid_amount <= 0) $noPayment++;
}

echo "\n... (showing 15 of " . count($invWithoutColl) . ")\n";

echo "\n=== BREAKDOWN ===\n\n";
echo "Total without matching collections: " . count($invWithoutColl) . "\n";
echo "  With ledger entry: {$withLedger} (from sample of 15)\n";
echo "  Without ledger entry: {$withoutLedger} (from sample of 15)\n";
echo "  paid_amount = 0: {$noPayment} (from sample of 15)\n";

// Full count
$fullWithLedger = DB::select('
    SELECT COUNT(*) as cnt FROM (
        SELECT si.id
        FROM sales_invoices si
        LEFT JOIN (
            SELECT sales_invoice_id, SUM(amount) as coll_total
            FROM collections WHERE status = ? GROUP BY sales_invoice_id
        ) c ON c.sales_invoice_id = si.id
        LEFT JOIN customer_ledger cl ON cl.reference_type = ? AND cl.reference_id = si.id
        WHERE si.deleted_at IS NULL
        AND (c.coll_total IS NULL OR ABS(si.paid_amount - c.coll_total) > 0.01)
        AND cl.id IS NOT NULL
    )
', ['approved', 'sales_invoice']);

$fullWithoutLedger = DB::select('
    SELECT COUNT(*) as cnt FROM (
        SELECT si.id
        FROM sales_invoices si
        LEFT JOIN (
            SELECT sales_invoice_id, SUM(amount) as coll_total
            FROM collections WHERE status = ? GROUP BY sales_invoice_id
        ) c ON c.sales_invoice_id = si.id
        LEFT JOIN customer_ledger cl ON cl.reference_type = ? AND cl.reference_id = si.id
        WHERE si.deleted_at IS NULL
        AND (c.coll_total IS NULL OR ABS(si.paid_amount - c.coll_total) > 0.01)
        AND cl.id IS NULL
    )
', ['approved', 'sales_invoice']);

$fullNoPayment = DB::select('
    SELECT COUNT(*) as cnt FROM (
        SELECT si.id
        FROM sales_invoices si
        LEFT JOIN (
            SELECT sales_invoice_id, SUM(amount) as coll_total
            FROM collections WHERE status = ? GROUP BY sales_invoice_id
        ) c ON c.sales_invoice_id = si.id
        WHERE si.deleted_at IS NULL
        AND (c.coll_total IS NULL OR ABS(si.paid_amount - c.coll_total) > 0.01)
        AND si.paid_amount <= 0
    )
', ['approved']);

$fullHasPayment = DB::select('
    SELECT COUNT(*) as cnt FROM (
        SELECT si.id
        FROM sales_invoices si
        LEFT JOIN (
            SELECT sales_invoice_id, SUM(amount) as coll_total
            FROM collections WHERE status = ? GROUP BY sales_invoice_id
        ) c ON c.sales_invoice_id = si.id
        WHERE si.deleted_at IS NULL
        AND (c.coll_total IS NULL OR ABS(si.paid_amount - c.coll_total) > 0.01)
        AND si.paid_amount > 0.01
    )
', ['approved']);

echo "\n=== FULL COUNTS (all " . count($invWithoutColl) . " invoices) ===\n\n";
echo "  With ledger entry: {$fullWithLedger[0]->cnt}\n";
echo "  Without ledger entry: {$fullWithoutLedger[0]->cnt}\n";
echo "  paid_amount = 0: {$fullNoPayment[0]->cnt}\n";
echo "  paid_amount > 0: {$fullHasPayment[0]->cnt}\n";

// What are these zero-payment invoices?
echo "\n=== INVOICES WITH paid_amount=0 AND NO COLLECTIONS (sample 10) ===\n\n";
$zeroPaid = DB::select('
    SELECT si.id, si.customer_id, si.net_total, si.paid_amount, si.remaining_amount, si.invoice_date
    FROM sales_invoices si
    LEFT JOIN (
        SELECT sales_invoice_id, SUM(amount) as coll_total
        FROM collections WHERE status = ? GROUP BY sales_invoice_id
    ) c ON c.sales_invoice_id = si.id
    WHERE si.deleted_at IS NULL
    AND (c.coll_total IS NULL OR ABS(si.paid_amount - c.coll_total) > 0.01)
    AND si.paid_amount <= 0
    ORDER BY si.id
    LIMIT 10
', ['approved']);

foreach ($zeroPaid as $z) {
    $ledger = DB::table('customer_ledger')
        ->where('reference_type', 'sales_invoice')
        ->where('reference_id', $z->id)
        ->first();
    $ledgerInfo = $ledger ? "D:{$ledger->debit} C:{$ledger->credit}" : "NO LEDGER";
    echo "Inv #{$z->id} | Cust:{$z->customer_id} | Net:{$z->net_total} | Paid:{$z->paid_amount} | Remain:{$z->remaining_amount} | Date:{$z->invoice_date} | Ledger:{$ledgerInfo}\n";
}
