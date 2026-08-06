<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPermission extends Model
{
    protected $fillable = [
        'subscription_plan_id',
        'permission_code',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}
