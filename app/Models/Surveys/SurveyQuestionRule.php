<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestionRule extends Model
{
    use SoftDeletes;

    protected $table = 'survey_question_rules';

    protected $fillable = [
        'survey_question_id',
        'parent_question_id',
        'operator',
        'expected_value',
        'action_type',
    ];

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    public function parentQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class, 'parent_question_id');
    }
}
