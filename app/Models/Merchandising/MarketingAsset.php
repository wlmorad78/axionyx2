<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\CRM\CustomerMarketingAsset;

class MarketingAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_assets';

    protected $fillable = [
        'company_id',
        'asset_code',
        'marketing_asset_category_id',
        'serial_no',
        'asset_name',
        'brand',
        'model',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(MarketingAssetCategory::class, 'marketing_asset_category_id');
    }

    public function customerAssets()
    {
        return $this->hasMany(CustomerMarketingAsset::class, 'marketing_asset_id');
    }

    public function movements()
    {
        return $this->hasMany(MarketingAssetMovement::class, 'marketing_asset_id');
    }

    public function maintenance()
    {
        return $this->hasMany(MarketingAssetMaintenance::class, 'marketing_asset_id');
    }
}
