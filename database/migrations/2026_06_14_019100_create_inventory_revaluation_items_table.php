<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_revaluation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_revaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->decimal('old_cost', 15, 4);
            $table->decimal('new_cost', 15, 4);
            $table->decimal('difference', 15, 4);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_revaluation_items'); }
};
