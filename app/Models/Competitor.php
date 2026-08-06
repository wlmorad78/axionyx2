<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
    use SoftDeletes;

    protected $table = 'competitors';

    protected $fillable = [
        'company_id',
        'competitor_code',
        'competitor_name',
        'contact_person',
        'mobile',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(CompetitorBrand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CompetitorProduct::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(CompetitorPromotion::class);
    }

    public function newProducts(): HasMany
    {
        return $this->hasMany(CompetitorNewProduct::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CompetitorPhoto::class);
    }
}
