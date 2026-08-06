<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quantity_price_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rule_item_id')->constrained('pricing_rule_items')->cascadeOnDelete();
            $table->integer('from_qty')->default(0);
            $table->integer('to_qty')->default(0);
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('discount_percent', 6, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('quantity_price_breaks'); }
};
