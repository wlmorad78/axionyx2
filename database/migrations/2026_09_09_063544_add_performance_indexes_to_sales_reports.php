<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index(['company_id', 'invoice_date', 'status'], 'idx_si_company_date_status');
            $table->index(['company_id', 'customer_id', 'invoice_date'], 'idx_si_company_customer_date');
            $table->index(['company_id', 'sales_rep_id', 'invoice_date'], 'idx_si_company_rep_date');
            $table->index(['invoice_date'], 'idx_si_invoice_date');
            $table->index(['status', 'deleted_at'], 'idx_si_status_deleted');
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->index(['sales_invoice_id'], 'idx_sii_invoice_id');
            $table->index(['item_id'], 'idx_sii_item_id');
        });

        Schema::table('route_customers', function (Blueprint $table) {
            $table->index(['customer_id', 'is_active'], 'idx_rc_customer_active');
            $table->index(['route_id'], 'idx_rc_route_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index(['company_id', 'user_id'], 'idx_emp_company_user');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['company_id'], 'idx_cust_company');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_si_company_date_status');
            $table->dropIndex('idx_si_company_customer_date');
            $table->dropIndex('idx_si_company_rep_date');
            $table->dropIndex('idx_si_invoice_date');
            $table->dropIndex('idx_si_status_deleted');
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_sii_invoice_id');
            $table->dropIndex('idx_sii_item_id');
        });

        Schema::table('route_customers', function (Blueprint $table) {
            $table->dropIndex('idx_rc_customer_active');
            $table->dropIndex('idx_rc_route_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_emp_company_user');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_cust_company');
        });
    }
};
