<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_invoice_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('method_code', 50)->nullable()->comment('cash, bank_transfer, customer_balance, credit');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_invoice_id']);
            $table->index(['company_id', 'sales_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_payment_methods');
    }
};
