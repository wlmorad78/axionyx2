<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_tires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->string('serial_number', 100);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->enum('position', ['front_right','front_left','rear_right','rear_left','spare','warehouse'])->nullable();
            $table->date('installation_date')->nullable();
            $table->decimal('installation_km', 12, 2)->nullable();
            $table->decimal('current_km', 12, 2)->default(0);
            $table->enum('status', ['active','worn','damaged','retired'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'serial_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_tires'); }
};
