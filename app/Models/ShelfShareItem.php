<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShelfShareItem extends Model
{
    use SoftDeletes;

    protected $table = 'shelf_share_items';

    protected $fillable = [
        'shelf_share_survey_id',
        'brand_name',
        'facings_count',
        'shelf_percentage',
    ];

    protected $casts = [
        'shelf_percentage' => 'decimal:2',
        'facings_count' => 'integer',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(ShelfShareSurvey::class);
    }
}
