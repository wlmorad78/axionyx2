<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('load_request_id')->nullable()->constrained('load_requests');
            $table->foreignId('issue_order_id')->nullable()->constrained('issue_orders');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('payment_term_id')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->string('invoice_no', 50)->unique();
            $table->date('invoice_date');
            $table->time('invoice_time')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('item_discount_total', 12, 2)->default(0);
            $table->decimal('invoice_discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('incentive_total', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('sales_invoices'); }
};
