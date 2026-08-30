<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoadRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'request_no',
        'parent_load_request_id',
        'employee_id',
        'supervisor_employee_id',
        'supervisor_user_id',
        'sales_territory_id',
        'trip_date',
        'load_type',
        'priority',
        'request_date',
        'status',
        'total_items_count',
        'total_quantity',
        'total_amount',
        'requested_by',
        'create_by',
        'create_at',
        'create_notes',
        'notes',
        'user_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function supervisorEmployee()
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function salesTerritory()
    {
        return $this->belongsTo(SalesTerritory::class);
    }

    public function requestedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function createByEmployee()
    {
        return $this->belongsTo(Employee::class, 'create_by');
    }

    public function items()
    {
        return $this->hasMany(LoadRequestItem::class);
    }

    public function parentLoadRequest()
    {
        return $this->belongsTo(LoadRequest::class, 'parent_load_request_id');
    }

    public function parentRequest()
    {
        return $this->belongsTo(LoadRequest::class, 'parent_load_request_id');
    }

    public function issueOrder()
    {
        return $this->hasOne(IssueOrder::class);
    }
}
