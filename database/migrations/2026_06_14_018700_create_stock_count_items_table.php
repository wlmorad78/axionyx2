<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('unit_id')->constrained();
            $table->decimal('system_qty', 15, 2);
            $table->decimal('counted_qty', 15, 2);
            $table->decimal('variance_qty', 15, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stock_count_items'); }
};
