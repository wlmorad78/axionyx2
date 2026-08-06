<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class SurveyCategory extends Model
{
    use SoftDeletes;

    protected $table = 'survey_categories';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class, 'survey_category_id');
    }
}
