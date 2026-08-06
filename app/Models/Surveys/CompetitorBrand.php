<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorBrand extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_brands';

    protected $fillable = [
        'competitor_id',
        'brand_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CompetitorProduct::class);
    }
}
