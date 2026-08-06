<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'asset_category_id',
        'asset_code',
        'asset_name',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class);
    }
}
