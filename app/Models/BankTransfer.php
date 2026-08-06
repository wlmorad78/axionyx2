<?php
namespace App\Models;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankTransfer extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'from_bank_account_id', 'to_bank_account_id',
        'transfer_no', 'transfer_date', 'amount', 'description', 'notes',
        'status', 'created_by', 'approved_by', 'approved_at',
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

    public function fromBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id');
    }

    public function toBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
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
        static::creating(function (BankTransfer $model) {
            if (!$model->transfer_no) {
                $last = static::withTrashed()
                    ->orderByRaw("CAST(SUBSTR(transfer_no, 4) AS INTEGER) DESC")
                    ->first();
                $next = 1;
                if ($last && preg_match('/^BT-(\d+)$/', $last->transfer_no, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->transfer_no = 'BT-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
