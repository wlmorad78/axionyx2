<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salesman_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('salesman_id')->constrained('employees');
            $table->string('account_code', 50)->unique();
            $table->date('opening_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('total_sales', 15, 2)->default(0)->comment('إجمالي المشحون للمندوب');
            $table->decimal('total_returns', 15, 2)->default(0)->comment('إجمالي المرتجعات المقبولة');
            $table->decimal('total_collections', 15, 2)->default(0)->comment('إجمالي التحصيلات');
            $table->decimal('total_adjustments', 15, 2)->default(0)->comment('إجمالي التسويات والمخصصات');
            $table->decimal('current_balance', 15, 2)->default(0)->comment('الرصيد الحالي (مدين)');
            $table->decimal('total_debts', 15, 2)->default(0)->comment('إجمالي المديونيات المفتوحة');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesman_accounts');
    }
};