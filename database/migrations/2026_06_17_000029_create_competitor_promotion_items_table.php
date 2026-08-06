<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_promotion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_promotion_id')->constrained('competitor_promotions')->cascadeOnDelete();
            $table->foreignId('competitor_product_id')->constrained('competitor_products')->cascadeOnDelete();
            $table->enum('offer_type', ['DISCOUNT_PERCENT', 'DISCOUNT_AMOUNT', 'FREE_GOODS', 'BUNDLE', 'CASHBACK']);
            $table->decimal('offer_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_promotion_items');
    }
};
