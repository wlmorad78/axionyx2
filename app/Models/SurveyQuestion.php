<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'survey_questions';

    protected $fillable = [
        'survey_id',
        'question_no',
        'question_text',
        'question_type',
        'is_required',
        'allow_photo',
        'allow_comment',
        'display_order',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function options()
    {
        return $this->hasMany(SurveyQuestionOption::class);
    }

    public function rules()
    {
        return $this->hasMany(SurveyQuestionRule::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyResponseAnswer::class, 'survey_question_id');
    }
}
