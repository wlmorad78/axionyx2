<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_daily_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('shift_date');
            $table->decimal('start_km', 12, 2)->nullable();
            $table->decimal('end_km', 12, 2)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('notes')->nullable();
            $table->enum('status', ['IN_PROGRESS', 'COMPLETED'])->default('IN_PROGRESS');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_daily_shifts'); }
};
