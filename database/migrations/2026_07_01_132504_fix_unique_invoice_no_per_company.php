<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old global unique indexes and create composite (company_id + no) unique indexes
        // SQLite: must drop old index, then create new composite

        // sales_invoices
        DB::statement('DROP INDEX IF EXISTS sales_invoices_invoice_no_unique');
        DB::statement('CREATE UNIQUE INDEX sales_invoices_company_invoice_no_unique ON sales_invoices (company_id, invoice_no)');

        // purchase_invoices
        DB::statement('DROP INDEX IF EXISTS purchase_invoices_invoice_no_unique');
        DB::statement('CREATE UNIQUE INDEX purchase_invoices_company_invoice_no_unique ON purchase_invoices (company_id, invoice_no)');

        // payment_vouchers
        DB::statement('DROP INDEX IF EXISTS payment_vouchers_voucher_no_unique');
        DB::statement('CREATE UNIQUE INDEX payment_vouchers_company_voucher_no_unique ON payment_vouchers (company_id, voucher_no)');

        // inventory_transactions
        DB::statement('DROP INDEX IF EXISTS inventory_transactions_transaction_no_unique');
        DB::statement('CREATE UNIQUE INDEX inventory_transactions_company_transaction_no_unique ON inventory_transactions (company_id, transaction_no)');

        // journal_entries
        DB::statement('DROP INDEX IF EXISTS journal_entries_entry_no_unique');
        DB::statement('CREATE UNIQUE INDEX journal_entries_company_entry_no_unique ON journal_entries (company_id, entry_no)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sales_invoices_company_invoice_no_unique');
        DB::statement('CREATE UNIQUE INDEX sales_invoices_invoice_no_unique ON sales_invoices (invoice_no)');

        DB::statement('DROP INDEX IF EXISTS purchase_invoices_company_invoice_no_unique');
        DB::statement('CREATE UNIQUE INDEX purchase_invoices_invoice_no_unique ON purchase_invoices (invoice_no)');

        DB::statement('DROP INDEX IF EXISTS payment_vouchers_company_voucher_no_unique');
        DB::statement('CREATE UNIQUE INDEX payment_vouchers_voucher_no_unique ON payment_vouchers (voucher_no)');

        DB::statement('DROP INDEX IF EXISTS inventory_transactions_company_transaction_no_unique');
        DB::statement('CREATE UNIQUE INDEX inventory_transactions_transaction_no_unique ON inventory_transactions (transaction_no)');

        DB::statement('DROP INDEX IF EXISTS journal_entries_company_entry_no_unique');
        DB::statement('CREATE UNIQUE INDEX journal_entries_entry_no_unique ON journal_entries (entry_no)');
    }
};
