<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'category',
        'description',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function plans()
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_features', 'feature_id', 'subscription_plan_id')
            ->withPivot(['is_enabled', 'config'])
            ->withTimestamps();
    }
}
