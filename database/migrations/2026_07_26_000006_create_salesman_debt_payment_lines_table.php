<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salesman_debt_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('salesman_debt_id')->constrained('salesman_debts');
            $table->foreignId('salesman_account_id')->nullable()->constrained('salesman_accounts');
            $table->foreignId('salesman_id')->constrained('employees');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2)->default(0)->comment('المبلغ المدفوع في هذه القسط');
            $table->decimal('remaining_after_payment', 15, 2)->default(0)->comment('المديونية المتبقية بعد الدفعة');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries');
            $table->foreignId('collection_id')->nullable()->constrained('collections');
            $table->string('reference_no', 100)->nullable()->comment('رقم مرجعي (مثل إيصال نقدي)');
            $table->string('payment_type', 30)->default('cash')->comment('cash, bank, check');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('employees')->comment('أمين الخزنة أو من استلم');
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesman_debt_payment_lines');
    }
};