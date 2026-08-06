<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_fuel_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('fuel_station_id')->nullable()->constrained('vehicle_fuel_stations');
            $table->enum('fuel_type', ['gasoline', 'diesel']);
            $table->decimal('price_per_liter', 8, 4);
            $table->date('effective_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_fuel_prices');
    }
};
