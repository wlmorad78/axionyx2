<?php

namespace App\Models\Treasury;

use App\Models\BankAccount;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;

class TreasuryBankTransfer extends Model
{
    use SoftDeletes, BelongsToCompany, BranchScoped;

    protected $table = 'treasury_bank_transfers';

    protected $fillable = [
        'company_id', 'branch_id', 'transfer_no', 'transfer_type',
        'treasury_id', 'bank_account_id', 'transfer_date', 'amount',
        'description', 'notes', 'status', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    protected static function booted(): void
    {
        static::creating(function (TreasuryBankTransfer $model) {
            if (!$model->transfer_no) {
                $last = static::withTrashed()
                    ->orderByRaw("CAST(SUBSTR(transfer_no, 4) AS INTEGER) DESC")
                    ->first();
                $next = 1;
                if ($last && preg_match('/^TB-(\d+)$/', $last->transfer_no, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->transfer_no = 'TB-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
