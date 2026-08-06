<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CRM\Customer;
use App\Models\CRM\CustomerVisit;
use App\Models\HR\Employee;

class SurveyResponse extends Model
{
    use SoftDeletes;

    protected $table = 'survey_responses';

    protected $fillable = [
        'survey_id',
        'customer_id',
        'sales_rep_id',
        'visit_id',
        'response_date',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $casts = [
        'response_date' => 'date',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function visit()
    {
        return $this->belongsTo(CustomerVisit::class, 'visit_id');
    }

    public function answers()
    {
        return $this->hasMany(SurveyResponseAnswer::class);
    }

    public function photos()
    {
        return $this->hasMany(SurveyResponsePhoto::class);
    }

    public function scores()
    {
        return $this->hasMany(SurveyScore::class);
    }
}
