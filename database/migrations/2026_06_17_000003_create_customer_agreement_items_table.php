<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_agreement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_agreement_id')->constrained('customer_agreements');
            $table->foreignId('item_id')->nullable()->constrained('items');
            $table->foreignId('brand_id')->nullable()->constrained('product_companies');
            $table->foreignId('item_category_id')->nullable()->constrained('item_categories');
            $table->enum('discount_type', ['AMOUNT', 'PERCENT']);
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('target_qty', 10, 2)->default(0);
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_agreement_items');
    }
};
