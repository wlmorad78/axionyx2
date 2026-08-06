<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\User;

class Survey extends Model
{
    use SoftDeletes;

    protected $table = 'surveys';

    protected $fillable = [
        'company_id',
        'survey_category_id',
        'survey_code',
        'survey_name',
        'description',
        'start_date',
        'end_date',
        'is_mandatory',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(SurveyCategory::class, 'survey_category_id');
    }

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function assignments()
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    public function scoringRules()
    {
        return $this->hasMany(SurveyScoringRule::class);
    }

    public function scores()
    {
        return $this->hasMany(SurveyScore::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
