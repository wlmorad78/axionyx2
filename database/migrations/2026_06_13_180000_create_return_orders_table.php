<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('load_request_id')->nullable()->constrained('load_requests');
            $table->foreignId('issue_order_id')->nullable()->constrained('issue_orders');
            $table->string('return_no', 50)->unique();
            $table->string('return_type', 50)->default('damaged'); // damaged, expired, wrong_item, excess, quality_issue
            $table->date('return_date');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('sales_territory_id')->nullable()->constrained('sales_territories');
            $table->string('status_id', 20)->default('draft'); // draft, pending, approved, received, cancelled
            $table->integer('total_items_count')->default(0);
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('received_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('return_orders');
    }
};
