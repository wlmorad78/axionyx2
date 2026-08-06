<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_stock_count_id')->constrained('vehicle_stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('system_qty', 12, 2)->default(0);
            $table->decimal('actual_qty', 12, 2)->default(0);
            $table->decimal('variance_qty', 12, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_stock_count_items'); }
};
