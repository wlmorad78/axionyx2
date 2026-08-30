<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;

class SalesmanAccount extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $table = 'salesman_accounts';

    protected $fillable = [
        'company_id', 'branch_id', 'salesman_id', 'account_code',
        'opening_date', 'opening_balance', 'total_sales', 'total_returns',
        'total_collections', 'total_adjustments', 'current_balance',
        'total_debts', 'is_active', 'notes',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_returns' => 'decimal:2',
        'total_collections' => 'decimal:2',
        'total_adjustments' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'total_debts' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesmanAccount $model) {
            if (empty($model->account_code)) {
                $last = static::orderByRaw("CAST(SUBSTR(account_code, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^SMA-(\d+)$/', $last->account_code, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->account_code = 'SMA-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function salesman(): BelongsTo { return $this->belongsTo(Employee::class, 'salesman_id'); }
    public function movements(): HasMany { return $this->hasMany(SalesmanAccountMovement::class); }
    public function debts(): HasMany { return $this->hasMany(SalesmanDebt::class); }
}