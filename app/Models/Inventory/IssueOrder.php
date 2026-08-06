<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Sales\Route;

class IssueOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'load_request_id',
        'issue_no', 'issue_date', 'issue_time', 'employee_id',
        'sales_territory_id', 'route_id', 'vehicle_id', 'status',
        'total_items_count', 'total_quantity', 'total_amount',
        'issued_by', 'received_by', 'received_at',
        'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'issue_time' => 'datetime:H:i',
        'received_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_items_count' => 'integer',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function loadRequest() { return $this->belongsTo(LoadRequest::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function salesTerritory() { return $this->belongsTo(SalesTerritory::class); }
    public function route() { return $this->belongsTo(Route::class); }
    public function issuedByEmployee() { return $this->belongsTo(Employee::class, 'issued_by'); }
    public function receivedByEmployee() { return $this->belongsTo(Employee::class, 'received_by'); }
    public function approvedByEmployee() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(IssueOrderItem::class); }

    protected static function booted(): void
    {
        static::creating(function (IssueOrder $model) {
            if (!$model->issue_no) {
                $last = static::orderByRaw("CAST(SUBSTR(issue_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^IO-(\d+)$/', $last->issue_no, $m)) $next = intval($m[1]) + 1;
                $model->issue_no = 'IO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
