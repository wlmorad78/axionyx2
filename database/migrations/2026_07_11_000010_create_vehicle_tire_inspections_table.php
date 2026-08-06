<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_tire_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('tire_id')->constrained('vehicle_tires');
            $table->date('inspection_date');
            $table->decimal('tread_depth_mm', 5, 2)->nullable();
            $table->decimal('pressure_psi', 5, 1)->nullable();
            $table->text('condition_notes')->nullable();
            $table->string('inspected_by', 255)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_tire_inspections'); }
};
