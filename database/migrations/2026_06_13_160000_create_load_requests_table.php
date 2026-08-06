<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('load_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->string('request_no', 50)->unique();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('supervisor_employee_id')->nullable()->constrained('employees');
            $table->foreignId('sales_territory_id')->nullable()->constrained('sales_territories');
            $table->date('trip_date')->nullable();
            $table->string('load_type', 50)->default('standard'); // standard, express, priority
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->date('request_date');
            $table->string('status', 20)->default('draft'); // draft, pending, approved, rejected, loading, completed, cancelled
            $table->integer('total_items_count')->default(0);
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('requested_by')->nullable()->constrained('employees');
            $table->foreignId('create_by')->nullable()->constrained('employees');
            $table->timestamp('create_at')->nullable();
            $table->text('create_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('load_requests');
    }
};
