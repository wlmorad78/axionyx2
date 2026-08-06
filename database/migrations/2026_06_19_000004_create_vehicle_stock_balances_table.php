<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_warehouse_id')->constrained('vehicle_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('average_cost', 12, 4)->default(0);
            $table->decimal('stock_value', 14, 4)->default(0);
            $table->timestamps();
            $table->unique(['vehicle_warehouse_id', 'item_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_stock_balances'); }
};
