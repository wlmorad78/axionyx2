<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorProduct extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_products';

    protected $fillable = [
        'competitor_id',
        'competitor_brand_id',
        'product_code',
        'product_name',
        'category_id',
        'unit_id',
        'barcode',
        'package_size',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CompetitorBrand::class, 'competitor_brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function priceSurveyItems(): HasMany
    {
        return $this->hasMany(CompetitorPriceSurveyItem::class);
    }

    public function promotionItems(): HasMany
    {
        return $this->hasMany(CompetitorPromotionItem::class);
    }
}
