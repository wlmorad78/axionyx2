<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_cost_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->string('analysis_period', 20);
            $table->decimal('fuel_cost', 14, 2)->default(0);
            $table->decimal('maintenance_cost', 14, 2)->default(0);
            $table->decimal('insurance_cost', 14, 2)->default(0);
            $table->decimal('tire_cost', 14, 2)->default(0);
            $table->decimal('battery_cost', 14, 2)->default(0);
            $table->decimal('depreciation_cost', 14, 2)->default(0);
            $table->decimal('salary_cost', 14, 2)->default(0);
            $table->decimal('violation_fines', 14, 2)->default(0);
            $table->decimal('other_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('total_km', 12, 2)->default(0);
            $table->decimal('cost_per_km', 10, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_analysis');
    }
};
