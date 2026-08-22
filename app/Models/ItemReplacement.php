<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemReplacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_item_id',
        'original_item_id',
        'replacement_item_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function settlementItem()
    {
        return $this->belongsTo(ReturnOrderSettlementItem::class, 'settlement_item_id');
    }
}
