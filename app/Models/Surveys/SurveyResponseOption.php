<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;

class SurveyResponseOption extends Model
{
    public $timestamps = false;

    protected $table = 'survey_response_options';

    protected $fillable = [
        'survey_response_answer_id',
        'survey_question_option_id',
    ];

    public function answer()
    {
        return $this->belongsTo(SurveyResponseAnswer::class, 'survey_response_answer_id');
    }

    public function option()
    {
        return $this->belongsTo(SurveyQuestionOption::class, 'survey_question_option_id');
    }
}
