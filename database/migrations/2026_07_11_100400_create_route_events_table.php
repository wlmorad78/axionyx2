<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('route_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_route_id')->constrained('daily_routes');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->string('event_type', 50);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->dateTime('event_time');
            $table->string('severity', 20)->default('info');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('route_events');
    }
};
