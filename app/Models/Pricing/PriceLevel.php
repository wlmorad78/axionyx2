<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\Inventory\Item;
use App\Traits\BelongsToCompany;

class PriceLevel extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'price_levels';

    protected $fillable = [
        'company_id',
        'level_code',
        'level_name',
        'priority',
        'is_active',
    ];

    protected $casts = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customerLevels()
    {
        return $this->hasMany(CustomerPriceLevel::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_prices');
    }
}
