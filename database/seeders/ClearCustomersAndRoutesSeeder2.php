<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearCustomersAndRoutesSeeder2 extends Seeder
{
    public function run(): void
    {
        $isSQLite = config('database.default') === 'sqlite';

        if ($isSQLite) {
            DB::unprepared('PRAGMA foreign_keys = OFF');
        }

        $tables = [
            // === العملاء ===
            'customer_return_items',
            'customer_returns',
            'customer_opening_balances',
            'customer_price_lists',
            'customer_tax_profiles',
            'customer_marketing_materials',
            'customer_marketing_assets',
            'customer_rebate_rules',
            'customer_marketing_supports',
            'customer_agreement_history',
            'customer_agreement_payments',
            'customer_agreement_targets',
            'customer_agreement_items',
            'customer_agreements',
            'customer_agreement_types',
            'customer_special_prices',
            'customer_price_levels',
            'customer_ledger',
            'customer_credit_limits',
            'customer_visits',
            'customer_contacts',
            'customer_addresses',
            'customer_account_types',
            'customer_types',
            'customer_classes',
            'customer_groups',
            'customers',

            // === خطوط السير ===
            'daily_route_customers',
            'daily_routes',
            'route_events',
            'route_visits',
            'route_stops',
            'route_points',
            'route_assignments',
            'route_customers',
            'route_schedules',
            'route_templates',
            'routes',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
                $this->command->info("Cleared: {$table}");
            }
        }

        if ($isSQLite) {
            DB::unprepared('PRAGMA foreign_keys = ON');
        }

        $this->command->info("All customers and routes data cleared successfully!");
    }
}
