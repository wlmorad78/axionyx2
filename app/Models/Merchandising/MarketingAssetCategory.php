<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class MarketingAssetCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_asset_categories';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assets()
    {
        return $this->hasMany(MarketingAsset::class, 'marketing_asset_category_id');
    }
}
