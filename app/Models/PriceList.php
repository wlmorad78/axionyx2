<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class PriceList extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name_ar',
        'name_en',
        'is_default',
        'is_active',
        'is_taxable',
        'pricing_method_id',
        'priority',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'priority' => 'integer',
    ];

    public function pricingMethod()
    {
        return $this->belongsTo(PricingMethod::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function itemPrices()
    {
        return $this->hasMany(ItemPrice::class);
    }
}
