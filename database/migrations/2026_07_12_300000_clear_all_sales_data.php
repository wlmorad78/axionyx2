<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE FROM promotion_execution_logs WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM e_invoice_transactions WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM customer_returns WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM collections WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM sales_invoice_incentives WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM sales_invoice_taxes WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM sales_invoice_discounts WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::statement('DELETE FROM sales_invoice_items WHERE sales_invoice_id IN (SELECT id FROM sales_invoices)');
        DB::table('sales_invoices')->delete();

        DB::statement("UPDATE number_series SET next_sequence = 1 WHERE document_type = 'sales_invoice'");
    }

    public function down(): void
    {
        //
    }
};
