<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_batteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->string('serial_number', 100);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->decimal('voltage', 5, 1)->nullable();
            $table->decimal('capacity_ah', 6, 1)->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->enum('status', ['active','weak','replaced','dead'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'serial_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_batteries'); }
};
