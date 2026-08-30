<?php
namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\User;
use App\Models\HR\Employee;
use App\Models\Inventory\IssueOrder;
use App\Models\Inventory\Warehouse;

class ReturnOrder extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'load_request_id', 'issue_order_id',
        'return_no', 'return_type', 'return_purpose', 'return_date', 'employee_id', 'user_id',
        'sales_territory_id', 'status_id', 'salesman_account_id', 'salesman_debt_id',
        'total_items_count', 'total_quantity', 'total_amount', 'debt_impact',
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
    public function user() { return $this->belongsTo(User::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function salesTerritory() { return $this->belongsTo(SalesTerritory::class); }
    public function receivedByUser() { return $this->belongsTo(User::class, 'received_by'); }
    public function approvedByUser() { return $this->belongsTo(User::class, 'approved_by'); }
    public function receivedByEmployee() { return $this->belongsTo(Employee::class, 'received_by'); }
    public function approvedByEmployee() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(ReturnOrderItem::class); }
    public function salesmanAccount() { return $this->belongsTo(SalesmanAccount::class); }
    public function salesmanDebt() { return $this->belongsTo(SalesmanDebt::class); }

    protected static function booted(): void
    {
        static::creating(function (ReturnOrder $model) {
            if ($model->user_id && !$model->employee_id) {
                $employee = Employee::where('user_id', $model->user_id)->first();
                if ($employee) {
                    $model->employee_id = $employee->id;
                }
            }

            if (!$model->return_no) {
                $last = static::orderByRaw("CAST(SUBSTR(return_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^RO-(\d+)$/', $last->return_no, $m)) $next = intval($m[1]) + 1;
                $model->return_no = 'RO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
