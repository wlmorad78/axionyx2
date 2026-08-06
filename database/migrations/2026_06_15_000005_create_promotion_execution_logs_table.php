<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->foreignId('sales_incentive_id')->constrained('sales_incentives');
            $table->text('condition_result')->nullable();
            $table->text('reward_result')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_execution_logs');
    }
};
