<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributionPlanRep extends Model
{
    protected $fillable = [
        'distribution_plan_id', 'sales_rep_id', 'route_id',
        'avg_monthly_sales', 'rep_weight', 'total_quota',
    ];

    protected $casts = [
        'avg_monthly_sales' => 'decimal:2',
        'rep_weight' => 'decimal:4',
        'total_quota' => 'decimal:2',
    ];

    public function plan() { return $this->belongsTo(DistributionPlan::class, 'distribution_plan_id'); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function route() { return $this->belongsTo(\App\Models\Route::class); }
    public function customers() { return $this->hasMany(DistributionPlanCustomer::class, 'distribution_plan_rep_id'); }
}
