<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Support\RoleNames;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const FIRST_USERCODE = 1000;

    protected $fillable = [
        'usercode',
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'company_id',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    // ==================== Relationships ====================

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot('is_default');
    }

    public function defaultBranch(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->wherePivot('is_default', true)
            ->limit(1);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouses');
    }

    public function treasuries(): BelongsToMany
    {
        return $this->belongsToMany(Treasury::class, 'user_treasuries');
    }

    public function representative(): HasOne
    {
        return $this->hasOne(Representative::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    // ==================== Role Methods ====================

    public function isAdmin(): bool
    {
        return $this->roles->contains('name', RoleNames::ADMIN)
            || $this->roles->contains('name', 'super_admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->company_id === null;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles->whereIn('name', $roleNames)->isNotEmpty();
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->roles->contains('id', $role->id)) {
            $this->roles()->attach($role);
        }
    }

    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role);
        }
    }

    // ==================== Permission Methods ====================

    public function allPermissions(): \Illuminate\Support\Collection
    {
        return $this->roles->flatMap->permissions->unique('id');
    }

    public function hasPermissionTo(string $permissionCode): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->allPermissions()->contains('code', $permissionCode);
    }

    public function hasAnyPermission(array $permissionCodes): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->allPermissions()->whereIn('code', $permissionCodes)->isNotEmpty();
    }

    public function hasAllPermissions(array $permissionCodes): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $userPermissions = $this->allPermissions()->pluck('code')->toArray();
        return empty(array_diff($permissionCodes, $userPermissions));
    }

    // ==================== Company & Branch Methods ====================

    public function accessibleCompanies()
    {
        if ($this->isSuperAdmin()) {
            return Company::all();
        }
        return $this->companies;
    }

    public function currentCompanyId(): ?int
    {
        return $this->company_id;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }
}
