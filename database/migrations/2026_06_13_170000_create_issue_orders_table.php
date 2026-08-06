<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('issue_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('load_request_id')->nullable()->constrained('load_requests');
            $table->string('issue_no', 50)->unique();
            $table->date('issue_date');
            $table->time('issue_time')->nullable();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('sales_territory_id')->nullable()->constrained('sales_territories');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('status', 20)->default('draft'); // draft, pending, approved, issued, delivered, cancelled
            $table->integer('total_items_count')->default(0);
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('issued_by')->nullable()->constrained('employees');
            $table->foreignId('received_by')->nullable()->constrained('employees');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('issue_orders');
    }
};
