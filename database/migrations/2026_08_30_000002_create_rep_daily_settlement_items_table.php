<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rep_daily_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('settlement_id')->constrained('rep_daily_settlements')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->string('item_code', 50)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->decimal('loaded_qty', 12, 2)->default(0);
            $table->decimal('sold_qty', 12, 2)->default(0);
            $table->decimal('returned_qty', 12, 2)->default(0);
            $table->decimal('remaining_qty', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->decimal('transfer_in_qty', 12, 2)->default(0);
            $table->decimal('transfer_out_qty', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['settlement_id']);
            $table->index(['company_id', 'settlement_id']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_daily_settlement_items');
    }
};
