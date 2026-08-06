<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributionPlanProduct extends Model
{
    protected $fillable = [
        'distribution_plan_id', 'item_id',
        'available_qty', 'product_ratio',
    ];

    protected $casts = [
        'available_qty' => 'decimal:2',
        'product_ratio' => 'decimal:2',
    ];

    public function plan() { return $this->belongsTo(DistributionPlan::class, 'distribution_plan_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
