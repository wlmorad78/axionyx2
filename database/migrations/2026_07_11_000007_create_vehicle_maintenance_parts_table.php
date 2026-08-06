<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_maintenance_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_maintenance_id')->constrained('vehicle_maintenance');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty', 12, 2);
            $table->decimal('unit_cost', 12, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_parts');
    }
};
