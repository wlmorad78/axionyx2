<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('daily_distribution_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->date('dashboard_date');
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->integer('planned_customers')->default(0);
            $table->integer('visited_customers')->default(0);
            $table->integer('invoices_count')->default(0);
            $table->decimal('sales_amount', 12, 2)->default(0);
            $table->decimal('returns_amount', 12, 2)->default(0);
            $table->decimal('collections_amount', 12, 2)->default(0);
            $table->decimal('loaded_amount', 12, 2)->default(0);
            $table->decimal('settled_amount', 12, 2)->default(0);
            $table->decimal('cash_difference', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('daily_distribution_dashboards'); }
};
