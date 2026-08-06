<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerMarketingAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_marketing_assets';

    protected $fillable = [
        'marketing_asset_id',
        'customer_id',
        'agreement_id',
        'assigned_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    public function marketingAsset()
    {
        return $this->belongsTo(MarketingAsset::class, 'marketing_asset_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agreement()
    {
        return $this->belongsTo(CustomerAgreement::class, 'agreement_id');
    }
}
