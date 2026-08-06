<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;

class SalesIncentiveConditionItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'condition_id', 'item_id', 'required_qty',
    ];

    protected $casts = [
        'required_qty' => 'decimal:2',
    ];

    public function condition() { return $this->belongsTo(SalesIncentiveCondition::class, 'condition_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
