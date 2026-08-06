<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            $table->string('return_purpose', 30)->default('salesman_return')->after('return_type')->comment('salesman_return, warehouse_return, supplier_return');
            $table->foreignId('salesman_account_id')->nullable()->after('warehouse_id')->constrained('salesman_accounts');
            $table->foreignId('salesman_debt_id')->nullable()->after('salesman_account_id')->constrained('salesman_debts');
            $table->decimal('debt_impact', 12, 2)->default(0)->after('total_amount')->comment('تأثير الارتجاع على مديونية المندوب (سالب = تقليل مديونية)');
        });
    }

    public function down(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            $table->dropColumn(['return_purpose', 'salesman_account_id', 'salesman_debt_id', 'debt_impact']);
        });
    }
};