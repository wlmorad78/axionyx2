<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Warehouse;

class ReturnAuthorization extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $table = 'return_authorizations';

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'salesman_id',
        'salesman_account_id', 'customer_id', 'sales_route_id',
        'return_auth_no', 'return_date', 'return_time',
        'total_sales_value', 'total_return_value', 'net_debt_amount',
        'return_reason_id', 'status', 'notes',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'return_time' => 'datetime:H:i',
        'total_sales_value' => 'decimal:2',
        'total_return_value' => 'decimal:2',
        'net_debt_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReturnAuthorization $model) {
            if (empty($model->return_auth_no)) {
                $model->return_auth_no = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByRaw("CAST(SUBSTR(return_auth_no, 6) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^SRET-(\d+)$/', $last->return_auth_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'SRET-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function salesman(): BelongsTo { return $this->belongsTo(Employee::class, 'salesman_id'); }
    public function salesmanAccount(): BelongsTo { return $this->belongsTo(SalesmanAccount::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function salesRoute(): BelongsTo { return $this->belongsTo(Route::class, 'sales_route_id'); }
    public function items(): HasMany { return $this->hasMany(ReturnAuthorizationItem::class); }
    public function createdByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function salesmanDebt(): BelongsTo { return $this->belongsTo(SalesmanDebt::class); }

    public function recalculateTotals(): void
    {
        $acceptedItems = $this->items()->where('acceptance_status', 'accepted')->get();
        $totalReturnValue = $acceptedItems->sum('net_amount');
        $totalSalesValue = $acceptedItems->sum('gross_amount');

        $this->update([
            'total_return_value' => $totalReturnValue,
            'total_sales_value' => $totalSalesValue,
            'net_debt_amount' => bcsub((string)$totalSalesValue, (string)$totalReturnValue, 2),
        ]);
    }
}