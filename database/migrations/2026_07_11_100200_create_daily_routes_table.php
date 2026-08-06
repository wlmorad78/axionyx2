<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('daily_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes');
            $table->foreignId('employee_id')->nullable()->constrained('employees');
            $table->date('route_date');
            $table->string('status', 20)->default('planned');
            $table->time('planned_start_time')->nullable();
            $table->time('planned_end_time')->nullable();
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->integer('planned_customers')->default(0);
            $table->integer('visited_customers')->default(0);
            $table->decimal('total_distance_km', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('daily_routes');
    }
};
