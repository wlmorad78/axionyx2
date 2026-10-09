<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('minimum_quantity', 12, 4);
            $table->decimal('discount_per_carton', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('product_discount_rule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_discount_rule_id')
                ->constrained('product_discount_rules')
                ->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unique(['product_discount_rule_id', 'item_id'], 'product_discount_rule_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discount_rule_items');
        Schema::dropIfExists('product_discount_rules');
    }
};