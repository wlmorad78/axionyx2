<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class SalesmanDebt extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $table = 'salesman_debts';

    protected $fillable = [
        'company_id', 'branch_id', 'salesman_id', 'salesman_account_id',
        'return_authorization_id', 'salesman_assignment_id',
        'debt_no', 'debt_date', 'total_sales', 'total_returns',
        'gross_debt', 'total_paid', 'remaining_debt', 'writeoff_amount',
        'status', 'due_date', 'notes',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'debt_date' => 'date',
        'due_date' => 'date',
        'total_sales' => 'decimal:2',
        'total_returns' => 'decimal:2',
        'gross_debt' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'remaining_debt' => 'decimal:2',
        'writeoff_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesmanDebt $model) {
            if (empty($model->debt_no)) {
                $model->debt_no = static::generateDebtNo($model->company_id);
            }
        });
    }

    public static function generateDebtNo(?int $companyId = null): string
    {
        $query = static::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->orderByRaw("CAST(SUBSTR(debt_no, 7) AS UNSIGNED) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^SDEBT-(\d+)$/', $last->debt_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'SDEBT-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function salesman(): BelongsTo { return $this->belongsTo(Employee::class, 'salesman_id'); }
    public function salesmanAccount(): BelongsTo { return $this->belongsTo(SalesmanAccount::class); }
    public function returnAuthorization(): BelongsTo { return $this->belongsTo(ReturnAuthorization::class); }
    public function salesmanAssignment(): BelongsTo { return $this->belongsTo(SalesmanAssignment::class); }
    public function paymentLines(): HasMany { return $this->hasMany(SalesmanDebtPaymentLine::class); }
    public function createdByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'approved_by'); }
}