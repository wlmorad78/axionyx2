<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanySubscriptionLimit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_subscription_id',
        'max_branches',
        'max_warehouses',
        'max_treasuries',
    ];

    protected function casts(): array
    {
        return [
            'max_branches' => 'integer',
            'max_warehouses' => 'integer',
            'max_treasuries' => 'integer',
        ];
    }

    public function subscription()
    {
        return $this->belongsTo(CompanySubscription::class, 'company_subscription_id');
    }
}
