<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPriceSurveyItem extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_price_survey_items';

    protected $fillable = [
        'competitor_price_survey_id',
        'competitor_product_id',
        'price',
        'promotion_price',
        'stock_status',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promotion_price' => 'decimal:2',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(CompetitorPriceSurvey::class);
    }

    public function competitorProduct(): BelongsTo
    {
        return $this->belongsTo(CompetitorProduct::class);
    }
}
