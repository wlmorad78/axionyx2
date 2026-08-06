<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributionPlanCustomer extends Model
{
    protected $fillable = [
        'distribution_plan_id', 'distribution_plan_rep_id', 'customer_id',
        'avg_monthly_sales', 'customer_weight',
        'total_quota', 'allocated_qty', 'final_qty',
        'is_manual_override',
    ];

    protected $casts = [
        'avg_monthly_sales' => 'decimal:2',
        'customer_weight' => 'decimal:4',
        'total_quota' => 'decimal:2',
        'allocated_qty' => 'decimal:2',
        'final_qty' => 'decimal:2',
        'is_manual_override' => 'boolean',
    ];

    public function plan() { return $this->belongsTo(DistributionPlan::class, 'distribution_plan_id'); }
    public function rep() { return $this->belongsTo(DistributionPlanRep::class, 'distribution_plan_rep_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(DistributionPlanItem::class, 'distribution_plan_customer_id'); }
}
