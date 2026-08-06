<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_incentive_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_incentive_id')->constrained('sales_incentives');
            $table->string('condition_type', 50); // min_quantity, min_amount, min_invoices, target_items, target_brands
            $table->decimal('condition_value', 12, 2)->default(0);
            $table->string('condition_operator', 10)->default('>=');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sales_incentive_conditions');
    }
};
