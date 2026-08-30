<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'requires_bank_account',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_bank_account' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }
}
