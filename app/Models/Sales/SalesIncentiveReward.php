<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;

class SalesIncentiveReward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_incentive_id', 'reward_type', 'discount_type',
        'reward_value', 'max_reward', 'item_id', 'qty', 'notes',
    ];

    protected $casts = [
        'reward_value' => 'decimal:2',
        'max_reward' => 'decimal:2',
        'qty' => 'decimal:2',
    ];

    public function salesIncentive() { return $this->belongsTo(SalesIncentive::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
