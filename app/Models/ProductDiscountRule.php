<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDiscountRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'minimum_quantity',
        'discount_per_carton',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'minimum_quantity' => 'decimal:4',
        'discount_per_carton' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'product_discount_rule_items');
    }
}