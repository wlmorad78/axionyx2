<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingAssetMaintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_asset_maintenance';

    protected $fillable = [
        'marketing_asset_id',
        'maintenance_date',
        'maintenance_type',
        'cost',
        'vendor_name',
        'notes',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function marketingAsset()
    {
        return $this->belongsTo(MarketingAsset::class, 'marketing_asset_id');
    }
}
