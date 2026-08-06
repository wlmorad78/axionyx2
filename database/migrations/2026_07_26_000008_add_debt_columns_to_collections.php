<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->enum('collection_type', ['customer_collection', 'salesman_debt_collection'])->default('customer_collection')->after('sales_invoice_id')->comment('نوع التحصيل');
            $table->foreignId('debt_id')->nullable()->after('collection_type')->constrained('salesman_debts');
            $table->unsignedBigInteger('debt_payment_line_id')->nullable()->after('debt_id')->comment('رقم القسط المدفوع');
            $table->foreignId('collected_from_id')->nullable()->after('debt_payment_line_id')->constrained('employees')->comment('المندوب الذي تم التحصيل منه (مختلف عن sales_rep_id في حالة تحصيل مديونية)');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['collection_type', 'debt_id', 'debt_payment_line_id', 'collected_from_id']);
        });
    }
};