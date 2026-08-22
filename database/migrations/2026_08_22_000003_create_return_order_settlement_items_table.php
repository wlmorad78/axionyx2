<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_order_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('return_order_settlements')->cascadeOnDelete();
            $table->integer('item_id');
            $table->integer('unit_id')->nullable();
            $table->decimal('loaded_quantity', 12, 2)->default(0);
            $table->decimal('sold_quantity', 12, 2)->default(0);
            $table->decimal('returned_quantity', 12, 2)->default(0);
            $table->decimal('received_quantity', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('financial_difference', 12, 2)->default(0);
            $table->string('type')->default('balanced');
            $table->integer('replacement_item_id')->nullable();
            $table->decimal('replacement_quantity', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['settlement_id']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_order_settlement_items');
    }
};
