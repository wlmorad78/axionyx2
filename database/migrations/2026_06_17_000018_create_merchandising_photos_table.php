<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandising_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_visit_id')->constrained('merchandising_visits')->onDelete('cascade');
            $table->enum('photo_type', ['STORE_FRONT', 'SHELF', 'FRIDGE', 'DISPLAY', 'PROMOTION']);
            $table->string('file_path');
            $table->dateTime('taken_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandising_photos');
    }
};
