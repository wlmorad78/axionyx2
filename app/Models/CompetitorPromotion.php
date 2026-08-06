<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorPromotion extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_promotions';

    protected $fillable = [
        'competitor_id',
        'promotion_name',
        'start_date',
        'end_date',
        'description',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompetitorPromotionItem::class);
    }
}
