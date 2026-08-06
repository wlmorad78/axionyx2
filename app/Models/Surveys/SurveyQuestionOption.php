<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestionOption extends Model
{
    use SoftDeletes;

    protected $table = 'survey_question_options';

    protected $fillable = [
        'survey_question_id',
        'option_text',
        'option_value',
        'display_order',
    ];

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
