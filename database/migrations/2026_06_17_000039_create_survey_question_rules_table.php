<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_question_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->foreignId('parent_question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->enum('operator', ['=', '!=', '>', '<', '>=', '<=']);
            $table->string('expected_value');
            $table->enum('action_type', ['SHOW', 'HIDE', 'REQUIRE']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_question_rules');
    }
};
