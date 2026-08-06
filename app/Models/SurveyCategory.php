<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
