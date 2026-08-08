<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearTransactionalDataSeeder extends Seeder
{
    public function run(): void
    {
        $isSQLite = config('database.default') === 'sqlite';

        if ($isSQLite) {
            DB::unprepared('PRAGMA foreign_keys = OFF');
        }

        $tables = [
            'sales_invoice_incentives',
            'sales_invoice_taxes',
            'sales_invoice_discounts',
            'sales_invoice_items',
            'sales_invoices',
            'sales_incentive_condition_items',
            'sales_incentive_conditions',
            'sales_incentive_rewards',
            'sales_incentives',
            'sales_target_details',
            'sales_targets',
            'collections',
            'return_order_items',
            'return_orders',
            'customer_return_items',
            'customer_returns',

            'purchase_invoice_items',
            'purchase_invoices',
            'purchase_expenses',
            'purchase_return_items',
            'purchase_returns',
            'purchase_order_items',
            'purchase_orders',
            'purchase_receipt_items',
            'purchase_receipts',
            'purchase_request_items',
            'purchase_requests',

            'daily_distribution_dashboards',
            'distribution_plan_items',
            'distribution_plans',

            'issue_order_items',
            'issue_orders',
            'load_request_items',
            'load_requests',

            'rep_item_distributions',

            'inventory_transaction_items',
            'inventory_transactions',
            'inventory_opening_balances',

            'e_invoice_transactions',
            'promotion_execution_logs',
            'rep_daily_expenses',
            'rep_daily_settlements',
            'salesman_settlements',
            'vehicle_settlements',
            'vehicle_expenses',
            'vehicle_daily_expenses',
            'expenses',
            'tax_returns',

            'treasury_custody_transactions',
            'treasury_custodies',
            'treasury_closing_details',
            'treasury_daily_closings',
            'treasury_shift_transactions',
            'treasury_shifts',
            'treasury_count_details',
            'treasury_counts',
            'treasury_adjustments',
            'treasury_transfers',
            'treasury_bank_transfers',
            'treasury_opening_balances',
            'treasury_alerts',
            'treasury_transactions',

            'bank_supplier_payments',
            'bank_reconciliations',
            'bank_transfers',
            'bank_opening_balances',
            'receipt_vouchers',
            'payment_vouchers',

            'supplier_opening_balances',

            'opening_balance_document_lines',
            'opening_balance_documents',
            'opening_balances',

            'gps_tracking_sessions',
            'customer_visits',
            'route_visits',
            'merchandising_visits',
            'merchandising_audits',
            'merchandising_task_assignments',
            'competitor_price_surveys',
            'competitor_photos',
            'shelf_share_surveys',
            'market_issues',
            'survey_responses',
            'survey_assignments',

            'representative_stocks',
            'representative_stock_movements',
            'inventory_movements',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
                $this->command->info("Cleared: {$table}");
            }
        }

        if (Schema::hasTable('treasuries')) {
            DB::table('treasuries')->update(['opening_balance' => 0]);
            $this->command->info("Reset: treasuries.opening_balance = 0");
        }

        if (Schema::hasTable('bank_accounts')) {
            DB::table('bank_accounts')->update(['opening_balance' => 0, 'current_balance' => 0]);
            $this->command->info("Reset: bank_accounts.opening_balance & current_balance = 0");
        }

        if (Schema::hasTable('suppliers')) {
            DB::table('suppliers')->update(['opening_balance' => 0]);
            $this->command->info("Reset: suppliers.opening_balance = 0");
        }

        if (Schema::hasTable('number_series')) {
            DB::table('number_series')
                ->whereIn('document_type', [
                    'sales_invoice',
                    'purchase_invoice',
                    'purchase_order',
                    'purchase_receipt',
                    'purchase_return',
                    'customer_return',
                    'return_order',
                    'collection',
                    'payment_voucher',
                    'receipt_voucher',
                    'treasury_transfer',
                    'treasury_bank_transfer',
                    'treasury_adjustment',
                    'treasury_count',
                    'treasury_daily_closing',
                ])
                ->update(['next_sequence' => 1]);
            $this->command->info("Reset: number_series sequences");
        }

        if ($isSQLite) {
            DB::unprepared('PRAGMA foreign_keys = ON');
        }

        $this->command->info("All transactional data cleared successfully!");
    }
}
