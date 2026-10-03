<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PURCHASE INVOICE #11 ===\n\n";
$inv = DB::table('purchase_invoices')->where('id', 11)->first();
if ($inv) {
    echo json_encode($inv, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} else {
    echo "NOT FOUND\n";
}

echo "\n=== RELATED RECORDS ===\n\n";

$items = DB::table('purchase_invoice_items')->where('purchase_invoice_id', 11)->get();
echo "purchase_invoice_items: " . $items->count() . "\n";
foreach ($items as $i) echo "  ID:{$i->id} Item:{$i->item_id} Qty:{$i->quantity} Price:{$i->unit_price}\n";

$expenses = DB::table('purchase_expenses')->where('purchase_invoice_id', 11)->get();
echo "purchase_expenses: " . $expenses->count() . "\n";

$returns = DB::table('purchase_returns')->where('purchase_invoice_id', 11)->get();
echo "purchase_returns: " . $returns->count() . "\n";

$payments = DB::table('payment_vouchers')->where('purchase_invoice_id', 11)->get();
echo "payment_vouchers: " . $payments->count() . "\n";

$bankPayments = DB::table('bank_supplier_payments')->where('purchase_invoice_id', 11)->get();
echo "bank_supplier_payments: " . $bankPayments->count() . "\n";

$ledger = DB::table('supplier_ledger')->where('reference_type', 'purchase_invoice')->where('reference_id', 11)->get();
echo "supplier_ledger: " . $ledger->count() . "\n";
foreach ($ledger as $l) echo "  ID:{$l->id} D:{$l->debit} C:{$l->credit}\n";
