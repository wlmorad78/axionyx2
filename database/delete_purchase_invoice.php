<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DELETING PURCHASE INVOICE #11 ===\n\n";

DB::beginTransaction();
try {
    $items = DB::table('purchase_invoice_items')->where('purchase_invoice_id', 11)->get();
    echo "purchase_invoice_items: " . $items->count() . " records\n";
    foreach ($items as $i) echo "  ID:{$i->id} Item:{$i->item_id} Qty:{$i->qty} Price:{$i->price}\n";

    $expenses = DB::table('purchase_expenses')->where('purchase_invoice_id', 11)->count();
    echo "purchase_expenses: {$expenses}\n";

    $returns = DB::table('purchase_returns')->where('purchase_invoice_id', 11)->count();
    echo "purchase_returns: {$returns}\n";

    $payments = DB::table('payment_vouchers')->where('purchase_invoice_id', 11)->count();
    echo "payment_vouchers: {$payments}\n";

    $bankPayments = DB::table('bank_supplier_payments')->where('purchase_invoice_id', 11)->count();
    echo "bank_supplier_payments: {$bankPayments}\n";

    $ledger = DB::table('supplier_ledger')->where('reference_type', 'purchase_invoice')->where('reference_id', 11)->get();
    echo "supplier_ledger: " . $ledger->count() . " records\n";
    foreach ($ledger as $l) echo "  ID:{$l->id} D:{$l->debit} C:{$l->credit}\n";

    DB::table('purchase_invoice_items')->where('purchase_invoice_id', 11)->delete();
    DB::table('purchase_expenses')->where('purchase_invoice_id', 11)->delete();
    DB::table('purchase_returns')->where('purchase_invoice_id', 11)->delete();
    DB::table('payment_vouchers')->where('purchase_invoice_id', 11)->delete();
    DB::table('bank_supplier_payments')->where('purchase_invoice_id', 11)->delete();
    DB::table('supplier_ledger')->where('reference_type', 'purchase_invoice')->where('reference_id', 11)->delete();
    DB::table('purchase_invoices')->where('id', 11)->delete();

    DB::commit();
    echo "\nDELETED SUCCESSFULLY\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
