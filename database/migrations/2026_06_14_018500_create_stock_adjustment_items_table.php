<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('unit_id')->constrained();
            $table->decimal('system_qty', 15, 2);
            $table->decimal('actual_qty', 15, 2);
            $table->decimal('difference_qty', 15, 2);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('difference_value', 15, 4);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stock_adjustment_items'); }
};
