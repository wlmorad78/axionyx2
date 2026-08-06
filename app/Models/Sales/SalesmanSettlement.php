<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Inventory\IssueOrder;

class SalesmanSettlement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'settlement_no', 'settlement_date', 'sales_rep_id',
        'route_id', 'load_request_id', 'issue_order_id',
        'total_loaded_value', 'total_sales_value', 'total_returns_value',
        'total_collections_value', 'expected_cash', 'actual_cash', 'cash_difference',
        'notes', 'status', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'total_loaded_value' => 'decimal:2',
        'total_sales_value' => 'decimal:2',
        'total_returns_value' => 'decimal:2',
        'total_collections_value' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesmanSettlement $model) {
            if (empty($model->settlement_no)) {
                $model->settlement_no = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByRaw("CAST(SUBSTR(settlement_no, 5) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^STL-(\d+)$/', $last->settlement_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'STL-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function route() { return $this->belongsTo(Route::class); }
    public function loadRequest() { return $this->belongsTo(LoadRequest::class); }
    public function issueOrder() { return $this->belongsTo(IssueOrder::class); }
}
