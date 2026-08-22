<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_daily_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            // 0 يعني "عام / بدون مستودع محدد" (NULL لا يسمح به في المفتاح الفريد)
            $table->unsignedBigInteger('warehouse_id')->default(0)->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->date('balance_date')->index();
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('incoming_qty', 20, 4)->default(0);
            $table->decimal('outgoing_qty', 20, 4)->default(0);
            $table->decimal('closing_balance', 20, 4)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'warehouse_id', 'item_id', 'balance_date'], 'inv_daily_bal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_daily_balances');
    }
};
