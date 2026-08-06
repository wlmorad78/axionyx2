<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesIncentiveCondition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_incentive_id', 'condition_type', 'condition_value',
        'from_qty', 'to_qty', 'from_amount', 'to_amount',
        'condition_operator', 'notes',
    ];

    protected $casts = [
        'condition_value' => 'decimal:2',
        'from_qty' => 'decimal:2',
        'to_qty' => 'decimal:2',
        'from_amount' => 'decimal:2',
        'to_amount' => 'decimal:2',
    ];

    public function salesIncentive() { return $this->belongsTo(SalesIncentive::class); }
    public function conditionItems() { return $this->hasMany(SalesIncentiveConditionItem::class, 'condition_id'); }
}
