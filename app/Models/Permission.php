<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'module',
        'group',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public static function getModules(): array
    {
        return static::distinct()
            ->whereNotNull('module')
            ->pluck('module')
            ->toArray();
    }

    public static function getGroupsByModule(string $module): array
    {
        return static::where('module', $module)
            ->distinct()
            ->whereNotNull('group')
            ->pluck('group')
            ->toArray();
    }
}
