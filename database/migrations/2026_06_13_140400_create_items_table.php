<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('product_company_id')->nullable()->constrained('product_companies');
            $table->foreignId('item_category_id')->nullable()->constrained('item_categories');
            $table->foreignId('item_sub_category_id')->nullable()->constrained('item_sub_categories');
            $table->string('code', 50)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('short_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_expiry_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->foreignId('base_unit_id')->nullable()->constrained('units');
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units');
            $table->foreignId('sales_unit_id')->nullable()->constrained('units');
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->decimal('maximum_stock', 12, 2)->default(0);
            $table->decimal('reorder_quantity', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
