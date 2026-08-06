<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_price_survey_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_price_survey_id')->constrained('competitor_price_surveys')->cascadeOnDelete();
            $table->foreignId('competitor_product_id')->constrained('competitor_products')->cascadeOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('promotion_price', 12, 2)->nullable();
            $table->enum('stock_status', ['AVAILABLE', 'LOW_STOCK', 'OUT_OF_STOCK'])->default('AVAILABLE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_price_survey_items');
    }
};
