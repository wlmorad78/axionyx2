<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_item_id')->constrained('return_order_settlement_items')->cascadeOnDelete();
            $table->integer('original_item_id');
            $table->integer('replacement_item_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['settlement_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_replacements');
    }
};
