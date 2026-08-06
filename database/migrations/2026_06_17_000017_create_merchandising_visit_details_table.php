<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandising_visit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_visit_id')->constrained('merchandising_visits')->onDelete('cascade');
            $table->foreignId('checklist_id')->constrained('merchandising_checklists')->onDelete('cascade');
            $table->decimal('score', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandising_visit_details');
    }
};
