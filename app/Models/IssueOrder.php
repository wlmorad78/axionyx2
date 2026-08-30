<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssueOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'load_request_id',
        'issue_no',
        'issue_date',
        'issue_time',
        'employee_id',
        'user_id',
        'sales_territory_id',
        'route_id',
        'vehicle_id',
        'status',
        'total_items_count',
        'total_quantity',
        'total_amount',
        'issued_by',
        'received_by',
        'received_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function loadRequest()
    {
        return $this->belongsTo(LoadRequest::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(IssueOrderItem::class);
    }
}
