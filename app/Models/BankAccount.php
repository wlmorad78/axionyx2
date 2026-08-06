<?php
namespace App\Models;

use App\Models\Accounting\Account;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Settings\Currency;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'account_id', 'bank_name',
        'account_name', 'account_no', 'account_number', 'iban', 'swift_code',
        'branch_name', 'branch_code', 'currency_id', 'opening_balance', 'current_balance',
        'notes', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bankTransfers(): HasMany
    {
        return $this->hasMany(BankTransfer::class, 'from_bank_account_id');
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }
}
