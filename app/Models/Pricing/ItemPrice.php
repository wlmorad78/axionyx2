<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class ItemPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'price_list_id',
        'unit_id',
        'price',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
