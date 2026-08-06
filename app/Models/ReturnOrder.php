<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'load_request_id', 'issue_order_id',
        'return_no', 'return_type', 'return_date', 'employee_id',
        'sales_territory_id', 'status_id',
        'total_items_count', 'total_quantity', 'total_amount',
        'received_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'approved_at' => 'datetime',
        'total_items_count' => 'integer',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function loadRequest() { return $this->belongsTo(LoadRequest::class); }
    public function issueOrder() { return $this->belongsTo(IssueOrder::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function salesTerritory() { return $this->belongsTo(SalesTerritory::class); }
    public function receivedByEmployee() { return $this->belongsTo(Employee::class, 'received_by'); }
    public function approvedByEmployee() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(ReturnOrderItem::class); }

    protected static function booted(): void
    {
        static::creating(function (ReturnOrder $model) {
            if (!$model->return_no) {
                $last = static::orderByRaw("CAST(SUBSTR(return_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^RO-(\d+)$/', $last->return_no, $m)) $next = intval($m[1]) + 1;
                $model->return_no = 'RO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
