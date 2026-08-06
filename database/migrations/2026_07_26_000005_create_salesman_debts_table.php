<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salesman_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('salesman_id')->constrained('employees');
            $table->foreignId('salesman_account_id')->nullable()->constrained('salesman_accounts');
            $table->foreignId('return_authorization_id')->nullable()->constrained('return_authorizations');
            $table->foreignId('salesman_assignment_id')->nullable()->constrained('salesman_assignments');
            $table->string('debt_no', 50)->unique();
            $table->date('debt_date');
            $table->decimal('total_sales', 12, 2)->default(0)->comment('إجمالي المبيعات المرتبطة');
            $table->decimal('total_returns', 12, 2)->default(0)->comment('إجمالي المرتجعات المقبولة');
            $table->decimal('gross_debt', 12, 2)->default(0)->comment('إجمالي المديونية الأصلية');
            $table->decimal('total_paid', 12, 2)->default(0)->comment('إجمالي المحصل من التحصيلات');
            $table->decimal('remaining_debt', 12, 2)->default(0)->comment('المديونية المتبقية');
            $table->decimal('writeoff_amount', 12, 2)->default(0)->comment('المبلغ المصفى');
            $table->string('status', 20)->default('pending')->comment('pending, partially_paid, fully_paid, written_off');
            $table->date('due_date')->nullable()->comment('تاريخ الاستحقاق');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salesman_id', 'status']);
            $table->index(['debt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesman_debts');
    }
};