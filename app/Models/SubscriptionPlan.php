<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'tier',
        'package_name',
        'duration_months',
        'price',
        'monthly_price',
        'setup_price',
        'max_branches',
        'max_warehouses',
        'max_treasuries',
        'max_users',
        'description',
        'features',
        'grace_period_days',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'duration_months' => 'integer',
            'price' => 'decimal:2',
            'monthly_price' => 'decimal:2',
            'setup_price' => 'decimal:2',
            'max_branches' => 'integer',
            'max_warehouses' => 'integer',
            'max_treasuries' => 'integer',
            'max_users' => 'integer',
            'features' => 'array',
            'grace_period_days' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function modules()
    {
        return $this->belongsToMany(AdminModule::class, 'plan_modules', 'subscription_plan_id', 'module_id')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete', 'sort_order'])
            ->withTimestamps();
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'plan_features', 'subscription_plan_id', 'feature_id')
            ->withPivot(['is_enabled', 'config'])
            ->withTimestamps();
    }

    public function planPermissions()
    {
        return $this->hasMany(PlanPermission::class, 'subscription_plan_id');
    }

    public function limits()
    {
        return $this->hasMany(PlanLimit::class, 'subscription_plan_id');
    }

    public function getLimit(string $key, int $default = 0): int
    {
        $limit = $this->limits()->where('key', $key)->first();
        return $limit ? (int) $limit->value : $default;
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->planPermissions()
            ->where('permission_code', '*')
            ->orWhere('permission_code', $permissionCode)
            ->orWhere('permission_code', explode('.', $permissionCode)[0] . '.*')
            ->exists();
    }

    public function hasModule(string $moduleKey): bool
    {
        return $this->modules()->where('admin_modules.key', $moduleKey)->exists();
    }

    public function canAccess(string $moduleKey, string $permission = 'can_view'): bool
    {
        $module = $this->modules()->where('admin_modules.key', $moduleKey)->first();

        if (!$module) {
            return false;
        }

        return (bool) $module->pivot->{$permission};
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderByTier($query)
    {
        return $query->orderBy('tier');
    }

    public function scopeOrderBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }
}
