<?php
require 'D:/axionyx_erp/vendor/autoload.php';
$app = require_once 'D:/axionyx_erp/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$db = \Illuminate\Support\Facades\DB::connection()->getPdo();

$dateFrom = '2026-08-16';
$dateTo = '2026-08-16';

// OLD (broken) - string comparison
$sql1 = "SELECT COUNT(*) as cnt FROM sales_invoices WHERE invoice_date >= '{$dateFrom}' AND invoice_date <= '{$dateTo}' AND deleted_at IS NULL";
$r1 = $db->query($sql1)->fetch();
echo "OLD (broken): " . $r1['cnt'] . " results\n";

// NEW (fixed) - DATE() function
$sql2 = "SELECT COUNT(*) as cnt FROM sales_invoices WHERE DATE(invoice_date) >= '{$dateFrom}' AND DATE(invoice_date) <= '{$dateTo}' AND deleted_at IS NULL";
$r2 = $db->query($sql2)->fetch();
echo "NEW (fixed): " . $r2['cnt'] . " results\n";

// Show results
$sql3 = "SELECT id, invoice_no, branch_id, invoice_date, status FROM sales_invoices WHERE DATE(invoice_date) >= '{$dateFrom}' AND DATE(invoice_date) <= '{$dateTo}' AND deleted_at IS NULL";
$r3 = $db->query($sql3)->fetchAll();
echo "\nInvoices found:\n";
foreach ($r3 as $row) {
    echo "  id={$row['id']}, invoice_no={$row['invoice_no']}, branch_id={$row['branch_id']}, invoice_date={$row['invoice_date']}, status={$row['status']}\n";
}
