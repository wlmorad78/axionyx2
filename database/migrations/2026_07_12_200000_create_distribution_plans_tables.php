<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('plan_no', 50)->unique();
            $table->string('plan_name', 255)->nullable();
            $table->date('plan_date');
            $table->integer('history_months')->default(6);
            $table->decimal('allocation_factor', 8, 4)->default(1.0);
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->decimal('total_demand', 12, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft, calculated, approved, applied
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('distribution_plan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_plan_id')->constrained('distribution_plans')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('available_qty', 12, 2)->default(0);
            $table->decimal('product_ratio', 5, 2)->default(0); // percentage like 40.00
            $table->timestamps();
        });

        Schema::create('distribution_plan_reps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_plan_id')->constrained('distribution_plans')->cascadeOnDelete();
            $table->foreignId('sales_rep_id')->constrained('employees');
            $table->decimal('avg_monthly_sales', 12, 2)->default(0);
            $table->decimal('rep_weight', 8, 4)->default(0);
            $table->decimal('total_quota', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('distribution_plan_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_plan_id')->constrained('distribution_plans')->cascadeOnDelete();
            $table->foreignId('distribution_plan_rep_id')->constrained('distribution_plan_reps')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('avg_monthly_sales', 12, 2)->default(0);
            $table->decimal('customer_weight', 8, 4)->default(0);
            $table->decimal('total_quota', 12, 2)->default(0);
            $table->decimal('allocated_qty', 12, 2)->default(0);
            $table->decimal('final_qty', 12, 2)->default(0);
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();
        });

        Schema::create('distribution_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_plan_id')->constrained('distribution_plans')->cascadeOnDelete();
            $table->foreignId('distribution_plan_customer_id')->constrained('distribution_plan_customers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('historical_avg', 12, 2)->default(0);
            $table->decimal('historical_ratio', 5, 2)->default(0); // customer's own ratio for this product
            $table->decimal('allocated_qty', 12, 2)->default(0);
            $table->decimal('final_qty', 12, 2)->default(0);
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_plan_items');
        Schema::dropIfExists('distribution_plan_customers');
        Schema::dropIfExists('distribution_plan_reps');
        Schema::dropIfExists('distribution_plan_products');
        Schema::dropIfExists('distribution_plans');
    }
};
