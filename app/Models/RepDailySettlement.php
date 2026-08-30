<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\IssueOrder;

class RepDailySettlement extends Model
{
    use SoftDeletes;

    protected $table = 'rep_daily_settlements';

    protected $fillable = [
        'company_id', 'branch_id', 'settlement_no', 'settlement_uuid', 'settlement_date', 'sales_rep_id',
        'customer_type', 'counter', 'new_counter_number', 'return_notes',
        'issue_order_id', 'total_sales_value', 'total_collections_value',
        'total_expenses', 'total_from_balance', 'expected_cash', 'actual_cash',
        'cash_difference', 'shortage', 'shortage_status', 'salesman_debt_id',
        'notes', 'status',
        'created_by', 'approved_by',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'total_sales_value' => 'decimal:2',
        'total_collections_value' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_from_balance' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'shortage' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (RepDailySettlement $model) {
            if (empty($model->settlement_no)) {
                $model->settlement_no = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByRaw("CAST(SUBSTR(settlement_no, 5) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^RDS-(\d+)$/', $last->settlement_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'RDS-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function issueOrder() { return $this->belongsTo(IssueOrder::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function expenses() { return $this->hasMany(RepDailyExpense::class, 'settlement_id'); }
    public function items() { return $this->hasMany(RepDailySettlementItem::class, 'settlement_id'); }
    public function salesmanDebt() { return $this->belongsTo(SalesmanDebt::class, 'salesman_debt_id'); }
}
