<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('unit_id')->nullable()->constrained();
            $table->decimal('system_qty', 15, 2)->default(0);
            $table->decimal('counted_qty', 15, 2)->default(0);
            $table->decimal('variance_qty', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('variance_cost', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_items');
    }
};
