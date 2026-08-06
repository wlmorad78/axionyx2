<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Treasury extends Model
{
    use SoftDeletes, BelongsToCompany, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'treasury_type_id',
        'currency_id',
        'code',
        'name',
        'name_ar',
        'name_en',
        'opening_balance',
        'notes',
        'is_main',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function treasuryType()
    {
        return $this->belongsTo(TreasuryType::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions()
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    public function getBalanceAttribute(): float
    {
        $opening = (float) $this->opening_balance;
        $credits = (float) $this->transactions()->where('type', 'credit')->sum('amount');
        $debits = (float) $this->transactions()->where('type', 'debit')->sum('amount');
        return $opening + $credits - $debits;
    }
}
