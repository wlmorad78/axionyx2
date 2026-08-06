<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Account extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_type_id',
        'account_group_id',
        'parent_id',
        'account_code',
        'account_name',
        'description',
        'is_leaf',
        'allow_transactions',
        'normal_balance',
        'opening_balance',
        'current_balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_leaf' => 'boolean',
        'allow_transactions' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Account $account) {
            // Rule: is_posting (is_leaf=false) => allow_transactions=false
            if (!$account->is_leaf) {
                $account->allow_transactions = false;
            }
        });
    }

    // ---- Relationships ----

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function accountGroup()
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    // ---- Accessors ----

    public function getLevelAttribute(): int
    {
        return $this->parent_id ? $this->parent->level + 1 : 1;
    }

    public function getIsPostingAttribute(): bool
    {
        return $this->is_leaf;
    }

    public function getTypeNameAttribute(): string
    {
        return $this->accountType?->name ?? '';
    }

    public function getNatureAttribute(): string
    {
        return $this->accountType?->nature ?? '';
    }

    // ---- Scopes ----

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePosting($query)
    {
        return $query->where('is_leaf', true);
    }

    public function scopeHeaders($query)
    {
        return $query->where('is_leaf', false);
    }

    // ---- Helpers ----

    public function isDeletable(): bool
    {
        return $this->children()->count() === 0;
    }
}
