<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $company = DB::table('companies')
            ->where('name_ar', 'LIKE', '%عمر فكرى محمد بسيونى%')
            ->orWhere('name_en', 'LIKE', '%Omar Fakry%')
            ->orWhere('commercial_name_ar', 'LIKE', '%عمر فكرى%')
            ->first();

        if (!$company) return;

        $companyId = $company->id;

        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'sales_invoice_items', 'sales_invoice_taxes', 'sales_invoice_discounts',
            'sales_invoices', 'customer_returns', 'collections',
            'purchase_invoice_items', 'purchase_invoices',
            'payment_vouchers', 'receipt_vouchers',
            'inventory_transaction_items', 'inventory_transactions',
            'customer_contacts', 'customers',
            'supplier_contacts', 'suppliers',
            'employees',
            'item_units', 'items', 'categories', 'units',
            'treasuries', 'bank_accounts',
            'branches',
            'journal_entry_lines', 'journal_entries', 'accounts',
            'distribution_plans', 'sales_routes', 'sales_territories',
            'user_roles', 'users',
            'company_settings', 'company_sidebar',
            'companies',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    if ($table === 'companies') {
                        DB::table($table)->where('id', $companyId)->delete();
                    } else {
                        DB::table($table)->where('company_id', $companyId)->delete();
                    }
                } catch (\Exception $e) {
                    // تجاهل الأخطاء
                }
            }
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void {}
};
