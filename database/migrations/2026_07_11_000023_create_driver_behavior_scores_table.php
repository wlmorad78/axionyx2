<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_behavior_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->date('score_date');
            $table->integer('speeding_events')->default(0);
            $table->integer('harsh_braking_events')->default(0);
            $table->integer('harsh_acceleration_events')->default(0);
            $table->decimal('idle_time_minutes', 8, 2)->default(0);
            $table->decimal('fuel_efficiency_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->default(100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_behavior_scores');
    }
};
