<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_work_order_id')->constrained('vehicle_work_orders');
            $table->string('description', 255);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_work_order_items');
    }
};
