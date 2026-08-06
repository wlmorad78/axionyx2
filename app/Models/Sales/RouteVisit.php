<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;

class RouteVisit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'route_id', 'sales_rep_id', 'customer_id',
        'visit_date', 'visit_time', 'check_in_time', 'check_out_time',
        'latitude', 'longitude', 'visit_type', 'visit_status', 'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'date:H:i',
        'check_in_time' => 'date:H:i',
        'check_out_time' => 'date:H:i',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function route() { return $this->belongsTo(Route::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
