<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_daily_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('settlement_no', 50)->unique();
            $table->date('settlement_date');
            $table->foreignId('sales_rep_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('issue_order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_sales_value', 12, 2)->default(0);
            $table->decimal('total_collections_value', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('total_from_balance', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('actual_cash', 12, 2)->default(0);
            $table->decimal('cash_difference', 12, 2)->default(0);
            $table->decimal('shortage', 12, 2)->default(0);
            $table->enum('shortage_status', ['pending', 'paid_next_day'])->default('pending');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'sales_rep_id', 'settlement_date']);
        });

        Schema::create('rep_daily_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('settlement_id')->constrained('rep_daily_settlements')->cascadeOnDelete();
            $table->string('expense_type', 100);
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamps();

            $table->index(['settlement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_daily_expenses');
        Schema::dropIfExists('rep_daily_settlements');
    }
};
