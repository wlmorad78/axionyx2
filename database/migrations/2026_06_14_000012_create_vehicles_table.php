<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('vehicle_code', 50);
            $table->string('plate_number', 50);
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('model', 100)->nullable();
            $table->integer('year')->nullable();
            $table->decimal('capacity', 10, 2)->nullable();
            $table->enum('status', ['active','inactive','maintenance'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'vehicle_code']);
        });
    }
    public function down(): void { Schema::dropIfExists('vehicles'); }
};
