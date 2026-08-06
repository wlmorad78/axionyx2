<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyResponseAnswer extends Model
{
    use SoftDeletes;

    protected $table = 'survey_response_answers';

    protected $fillable = [
        'survey_response_id',
        'survey_question_id',
        'answer_text',
        'answer_numeric',
        'answer_date',
    ];

    protected $casts = [
        'answer_numeric' => 'decimal:2',
        'answer_date' => 'date',
    ];

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    public function selectedOptions()
    {
        return $this->hasMany(SurveyResponseOption::class, 'survey_response_answer_id');
    }
}
