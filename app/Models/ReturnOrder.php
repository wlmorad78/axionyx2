<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'uuid',
        'branch_id',
        'warehouse_id',
        'load_request_id',
        'issue_order_id',
        'return_no',
        'return_type',
        'return_purpose',
        'return_date',
        'employee_id',
        'user_id',
        'sales_territory_id',
        'status_id',
        'total_items_count',
        'total_quantity',
        'total_amount',
        'debt_impact',
        'salesman_account_id',
        'salesman_debt_id',
        'received_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function salesTerritory()
    {
        return $this->belongsTo(\App\Models\SalesTerritory::class);
    }

    public function receivedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    public function approvedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loadRequest()
    {
        return $this->belongsTo(LoadRequest::class);
    }

    public function issueOrder()
    {
        return $this->belongsTo(IssueOrder::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }
}
