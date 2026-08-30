<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_global',
        'is_system',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function widgets(): BelongsToMany
    {
        return $this->belongsToMany(DashboardWidget::class, 'role_widgets', 'role_id', 'dashboard_widget_id')
            ->withPivot(['is_visible', 'sort_order', 'width', 'config'])
            ->withTimestamps();
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function givePermissionTo(Permission $permission): void
    {
        if (!$this->permissions->contains('id', $permission->id)) {
            $this->permissions()->attach($permission);
        }
    }

    public function removePermissionTo(Permission $permission): void
    {
        $this->permissions()->detach($permission);
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->permissions->contains('code', $permissionCode);
    }

    public function hasPermissionTo(Permission $permission): bool
    {
        return $this->permissions->contains('id', $permission->id);
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId)
                ->orWhere('is_global', true);
        }
        return $query->whereNull('company_id');
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public static function findByCode(string $code, ?int $companyId = null): ?self
    {
        $query = static::where('code', $code);

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhere('is_global', true);
            });
        } else {
            $query->whereNull('company_id');
        }

        return $query->first();
    }
}
