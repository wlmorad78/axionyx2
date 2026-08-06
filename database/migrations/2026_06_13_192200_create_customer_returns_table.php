<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->string('return_no', 50)->unique();
            $table->date('return_date');
            $table->time('return_time')->nullable();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->unsignedBigInteger('return_reason_id')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('customer_returns'); }
};
