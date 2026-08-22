<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('item_code', 50)->nullable();
            $table->decimal('loaded_qty', 12, 2)->default(0);
            $table->decimal('sold_qty', 12, 2)->default(0);
            $table->decimal('returned_qty', 12, 2)->default(0);

            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'warehouse_id']);
            $table->index(['employee_id', 'item_id']);
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_sales_returns');
    }
};
