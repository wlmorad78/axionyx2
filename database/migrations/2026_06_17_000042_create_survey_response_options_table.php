<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_response_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_answer_id')->constrained('survey_response_answers')->cascadeOnDelete();
            $table->foreignId('survey_question_option_id')->constrained('survey_question_options')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_response_options');
    }
};
