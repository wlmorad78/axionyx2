<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_tire_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('tire_id')->constrained('vehicle_tires');
            $table->enum('movement_type', ['install','remove','transfer','retire']);
            $table->foreignId('from_vehicle_id')->nullable()->constrained('vehicles');
            $table->foreignId('to_vehicle_id')->nullable()->constrained('vehicles');
            $table->string('from_position', 50)->nullable();
            $table->string('to_position', 50)->nullable();
            $table->date('movement_date');
            $table->decimal('km_at_movement', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_tire_movements'); }
};
