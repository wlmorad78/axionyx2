<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyScoringRule extends Model
{
    use SoftDeletes;

    protected $table = 'survey_scoring_rules';

    protected $fillable = [
        'survey_id',
        'survey_question_id',
        'expected_answer',
        'score',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
