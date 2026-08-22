<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_order_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_no')->unique();
            $table->integer('return_order_id')->nullable();
            $table->integer('employee_id');
            $table->integer('warehouse_id')->nullable();
            $table->string('load_request_no')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('total_loaded', 12, 2)->default(0);
            $table->decimal('total_sold', 12, 2)->default(0);
            $table->decimal('total_returned', 12, 2)->default(0);
            $table->decimal('total_received', 12, 2)->default(0);
            $table->decimal('total_difference', 12, 2)->default(0);
            $table->decimal('total_financial_difference', 12, 2)->default(0);
            $table->decimal('total_debt', 12, 2)->default(0);
            $table->decimal('total_credit', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['employee_id']);
            $table->index(['return_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_order_settlements');
    }
};
