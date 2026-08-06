<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_item_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('return_order_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('loaded_qty', 12, 2)->default(0);
            $table->decimal('sold_qty', 12, 2)->default(0);
            $table->decimal('returned_qty', 12, 2)->default(0);
            $table->decimal('remaining_qty', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);

            $table->string('status', 20)->default('active');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'item_id']);
            $table->index(['issue_order_id']);
            $table->index(['return_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_item_distributions');
    }
};
