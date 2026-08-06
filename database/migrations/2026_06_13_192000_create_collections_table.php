<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('collection_no', 50)->unique();
            $table->date('collection_date');
            $table->time('collection_time')->nullable();
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices');
            $table->foreignId('payment_method_id')->nullable();
            $table->unsignedBigInteger('safe_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('collections'); }
};
