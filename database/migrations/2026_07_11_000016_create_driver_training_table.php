<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_training', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->string('training_name', 255);
            $table->enum('training_type', ['safety', 'defensive_driving', 'first_aid', 'hazmat', 'other']);
            $table->date('training_date');
            $table->date('expiry_date')->nullable();
            $table->string('provider', 255)->nullable();
            $table->string('certificate_no', 100)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_training');
    }
};
