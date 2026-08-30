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
        'user_type_id',
        'branch_id',
        'warehouse_id',
        'treasury_id',
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

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Treasury::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    /**
     * Virtual roles accessor for backward compatibility with old code
     * that expects a 'roles' relationship. Returns userType as a collection.
     */
    public function getRolesAttribute()
    {
        if (!$this->userType) {
            return collect();
        }
        return collect([(object) [
            'id' => $this->userType->id,
            'name' => $this->userType->name_ar,
            'code' => $this->userType->code,
        ]]);
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

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    // ==================== Role / User-Type Methods ====================
    // الأدوار استبدلت بنوع المستخدم (user_type). الدوال تُبقي نفس التوقيع
    // لكن تعمل فوق user_type لتقليل كسر باقي الكود. لا يوجد فحص صلاحيات (permissions)
    // في هذه المرحلة — الصلاحيات تُحدَّد لاحقاً عبر user_type_permissions.

    public function isAdmin(): bool
    {
        if ($this->company_id === null) {
            return true; // super admin
        }

        return $this->userType
            && in_array(strtolower($this->userType->name_ar), ['owner', 'admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->company_id === null;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->userType
            && strcasecmp($this->userType->name_ar, $roleName) === 0;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        if (!$this->userType) {
            return false;
        }

        $current = strtolower($this->userType->name_ar);

        return collect($roleNames)->map(fn ($n) => strtolower($n))->contains($current);
    }

    public function assignRole(string $roleName): void
    {
        $type = UserType::where(function ($q) {
            $q->where('company_id', $this->company_id)
              ->orWhereNull('company_id');
        })->where('name_ar', $roleName)->first();

        if ($type) {
            $this->user_type_id = $type->id;
            $this->save();
        }
    }

    public function removeRole(string $roleName): void
    {
        if ($this->hasRole($roleName)) {
            $this->user_type_id = null;
            $this->save();
        }
    }

    // ==================== Permission Methods ====================

    public function allPermissions(): \Illuminate\Support\Collection
    {
        // لا يوجد نظام صلاحيات في هذه المرحلة
        return collect();
    }

    public function hasPermissionTo(string $permissionCode): bool
    {
        // لا يوجد فحص صلاحيات حالياً
        return true;
    }

    public function hasAnyPermission(array $permissionCodes): bool
    {
        return true;
    }

    public function hasAllPermissions(array $permissionCodes): bool
    {
        return true;
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
