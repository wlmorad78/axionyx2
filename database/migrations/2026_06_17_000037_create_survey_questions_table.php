<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
            $table->integer('question_no');
            $table->string('question_text');
            $table->enum('question_type', ['TEXT', 'TEXTAREA', 'NUMBER', 'DATE', 'TIME', 'YES_NO', 'SINGLE_CHOICE', 'MULTIPLE_CHOICE', 'RATING', 'PHOTO']);
            $table->boolean('is_required')->default(true);
            $table->boolean('allow_photo')->default(false);
            $table->boolean('allow_comment')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
