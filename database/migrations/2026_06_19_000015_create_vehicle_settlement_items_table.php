<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_settlement_id')->constrained('vehicle_settlements')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('opening_qty', 12, 2)->default(0);
            $table->decimal('loaded_qty', 12, 2)->default(0);
            $table->decimal('sold_qty', 12, 2)->default(0);
            $table->decimal('returned_qty', 12, 2)->default(0);
            $table->decimal('closing_qty', 12, 2)->default(0);
            $table->decimal('variance_qty', 12, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_settlement_items'); }
};
