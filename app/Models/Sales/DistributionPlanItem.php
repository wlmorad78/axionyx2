<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;

class DistributionPlanItem extends Model
{
    protected $fillable = [
        'distribution_plan_id', 'distribution_plan_customer_id', 'item_id',
        'historical_avg', 'historical_ratio',
        'allocated_qty', 'final_qty',
        'is_manual_override',
    ];

    protected $casts = [
        'historical_avg' => 'decimal:2',
        'historical_ratio' => 'decimal:2',
        'allocated_qty' => 'decimal:2',
        'final_qty' => 'decimal:2',
        'is_manual_override' => 'boolean',
    ];

    public function plan() { return $this->belongsTo(DistributionPlan::class, 'distribution_plan_id'); }
    public function customerPlan() { return $this->belongsTo(DistributionPlanCustomer::class, 'distribution_plan_customer_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
