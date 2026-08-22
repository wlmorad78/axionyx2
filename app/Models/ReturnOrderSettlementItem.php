<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnOrderSettlementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_id',
        'item_id',
        'unit_id',
        'loaded_quantity',
        'sold_quantity',
        'returned_quantity',
        'received_quantity',
        'difference',
        'unit_price',
        'financial_difference',
        'type',
        'replacement_item_id',
        'replacement_quantity',
    ];

    protected $casts = [
        'loaded_quantity' => 'decimal:2',
        'sold_quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'difference' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'financial_difference' => 'decimal:2',
        'replacement_quantity' => 'decimal:2',
    ];

    public function settlement()
    {
        return $this->belongsTo(ReturnOrderSettlement::class, 'settlement_id');
    }

    public function replacements()
    {
        return $this->hasMany(ItemReplacement::class, 'settlement_item_id');
    }
}
