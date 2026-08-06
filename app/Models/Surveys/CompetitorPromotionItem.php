<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPromotionItem extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_promotion_items';

    protected $fillable = [
        'competitor_promotion_id',
        'competitor_product_id',
        'offer_type',
        'offer_value',
        'notes',
    ];

    protected $casts = [
        'offer_value' => 'decimal:2',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(CompetitorPromotion::class);
    }

    public function competitorProduct(): BelongsTo
    {
        return $this->belongsTo(CompetitorProduct::class);
    }
}
