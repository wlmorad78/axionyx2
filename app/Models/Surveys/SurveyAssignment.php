<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use App\Models\Sales\Route;

class SurveyAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'survey_assignments';

    protected $fillable = [
        'survey_id',
        'sales_rep_id',
        'route_id',
        'customer_id',
        'assigned_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
