<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CRM\Customer;
use App\Models\User;

class MarketingAssetMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_asset_movements';

    protected $fillable = [
        'marketing_asset_id',
        'movement_date',
        'movement_type',
        'from_customer_id',
        'to_customer_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function marketingAsset()
    {
        return $this->belongsTo(MarketingAsset::class, 'marketing_asset_id');
    }

    public function fromCustomer()
    {
        return $this->belongsTo(Customer::class, 'from_customer_id');
    }

    public function toCustomer()
    {
        return $this->belongsTo(Customer::class, 'to_customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
