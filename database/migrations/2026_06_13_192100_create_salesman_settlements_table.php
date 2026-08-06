<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salesman_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('settlement_no', 50)->unique();
            $table->date('settlement_date');
            $table->foreignId('sales_rep_id')->constrained('employees');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->foreignId('load_request_id')->nullable()->constrained('load_requests');
            $table->foreignId('issue_order_id')->nullable()->constrained('issue_orders');
            $table->decimal('total_loaded_value', 12, 2)->default(0);
            $table->decimal('total_sales_value', 12, 2)->default(0);
            $table->decimal('total_returns_value', 12, 2)->default(0);
            $table->decimal('total_collections_value', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('actual_cash', 12, 2)->default(0);
            $table->decimal('cash_difference', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('salesman_settlements'); }
};
