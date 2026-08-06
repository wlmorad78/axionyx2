<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
