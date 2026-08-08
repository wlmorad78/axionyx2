<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'unit_id',
        'conversion_factor',
        'barcode',
        'is_purchase_unit',
        'is_sales_unit',
        'is_default',
        'purchase_price',
        'sale_price',
        'consumer_price',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'is_purchase_unit' => 'boolean',
        'is_sales_unit' => 'boolean',
        'is_default' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'consumer_price' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
