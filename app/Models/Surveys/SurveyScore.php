<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyScore extends Model
{
    use SoftDeletes;

    protected $table = 'survey_scores';

    protected $fillable = [
        'survey_response_id',
        'total_score',
        'max_score',
        'achievement_percent',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'achievement_percent' => 'decimal:2',
    ];

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }
}
