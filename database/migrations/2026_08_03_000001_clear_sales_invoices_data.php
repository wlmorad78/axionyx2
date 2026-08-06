<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'promotion_execution_logs',
            'e_invoice_transactions',
            'customer_returns',
            'customer_return_items',
            'collections',
            'sales_invoice_incentives',
            'sales_invoice_taxes',
            'sales_invoice_discounts',
            'sales_invoice_items',
            'sales_invoices',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement("UPDATE number_series SET next_sequence = 1 WHERE document_type = 'sales_invoice'");

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        //
    }
};
