<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_medical_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->string('test_type', 100);
            $table->date('test_date');
            $table->string('result', 100)->nullable();
            $table->date('next_test_date')->nullable();
            $table->string('doctor_name', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_medical_tests');
    }
};
