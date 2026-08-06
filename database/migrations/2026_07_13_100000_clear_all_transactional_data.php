<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'promotion_execution_logs',
            'e_invoice_transactions',
            'sales_invoice_incentives',
            'sales_invoice_taxes',
            'sales_invoice_discounts',
            'sales_invoice_items',
            'sales_invoices',
            'customer_return_items',
            'customer_returns',
            'collections',
            'salesman_settlements',
            'return_order_items',
            'return_orders',
            'purchase_expenses',
            'purchase_invoice_items',
            'purchase_invoices',
            'purchase_returns',
            'inventory_transaction_items',
            'inventory_transactions',
            'treasury_transactions',
            'vehicle_settlements',
            'tax_returns',
            'gps_tracking_sessions',
            'load_request_items',
            'load_requests',
            'issue_order_items',
            'issue_orders',
            'customer_visits',
            'route_visits',
            'daily_distribution_dashboards',
            'distribution_plan_items',
            'distribution_plans',
            'merchandising_visits',
            'merchandising_audits',
            'merchandising_task_assignments',
            'competitor_price_surveys',
            'competitor_photos',
            'shelf_share_surveys',
            'market_issues',
            'survey_responses',
            'survey_assignments',
            'number_series',
        ];

        /*
         * PostgreSQL supports deferring foreign key constraints.
         * SQLite does not support SET CONSTRAINTS.
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
        }

        /*
         * Disable foreign key checks temporarily for SQLite.
         */
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        /*
         * Delete data from tables that actually exist.
         *
         * Using Schema::hasTable() makes this compatible
         * with both SQLite and PostgreSQL.
         */
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        /*
         * Reset document number sequences.
         */
        if (Schema::hasTable('number_series')) {
            DB::table('number_series')
                ->where('document_type', 'sales_invoice')
                ->update([
                    'next_sequence' => 1,
                ]);

            DB::table('number_series')
                ->where('document_type', 'purchase_invoice')
                ->update([
                    'next_sequence' => 1,
                ]);
        }

        /*
         * Re-enable foreign key checks for SQLite.
         */
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        /*
         * Restore PostgreSQL constraint behavior.
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        }
    }

    public function down(): void
    {
        // This migration only clears transactional data.
        // There is nothing to rollback.
    }
};