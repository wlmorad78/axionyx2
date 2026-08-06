<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('daily_route_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_route_id')->constrained('daily_routes');
            $table->foreignId('customer_id')->constrained('customers');
            $table->integer('visit_order')->default(0);
            $table->time('planned_time')->nullable();
            $table->time('actual_check_in')->nullable();
            $table->time('actual_check_out')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('visit_status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('daily_route_customers');
    }
};
