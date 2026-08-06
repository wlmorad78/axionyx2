<?php

namespace App\Services;

use App\Models\User;
use App\Support\RoleNames;

class PermissionService
{
    /**
     * Check if the user has a specific permission.
     *
     * Supports:
     *   - Exact match:    'sales.invoice.view'
     *   - Wildcard:       'sales.invoice.*'     (any action on resource)
     *   - Module wildcard: 'sales.*'            (any resource in module)
     *   - Global wildcard: '*'                  (everything)
     */
    public function check(User $user, string $permission): bool
    {
        // Admin always has everything
        if ($this->isAdmin($user)) {
            return true;
        }

        $userPermissions = $this->getUserPermissions($user);

        return $this->matchesAny($permission, $userPermissions);
    }

    /**
     * Check multiple permissions — user must have ALL.
     */
    public function checkAll(User $user, array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (!$this->check($user, $p)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check multiple permissions — user must have ANY.
     */
    public function checkAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->check($user, $p)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all permissions the user has (resolved from roles + direct).
     */
    public function getUserPermissions(User $user): array
    {
        $permissions = [];

        // From roles
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $perm) {
                $permissions[] = $perm->code;
            }
        }

        // From direct permissions (if relation exists)
        if (method_exists($user, 'permissions') && $user->relationLoaded('permissions')) {
            foreach ($user->permissions as $perm) {
                $permissions[] = $perm->code;
            }
        }

        return array_unique($permissions);
    }

    /**
     * Get all defined permission strings (from config).
     */
    public function getAllDefinedPermissions(): array
    {
        return array_keys(config('permissions.definitions', []));
    }

    /**
     * Get permission descriptions grouped by module.
     */
    public function getPermissionsByModule(): array
    {
        $definitions = config('permissions.definitions', []);
        $grouped = [];

        foreach ($definitions as $perm => $description) {
            $parts = explode('.', $perm);
            $module = $parts[0] ?? 'other';
            $grouped[$module][$perm] = $description;
        }

        return $grouped;
    }

    // ─── Helpers ───────────────────────────────────────────

    protected function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleNames::ADMIN) || $user->hasRole('super_admin');
    }

    /**
     * Check if $permission matches any of $userPermissions.
     */
    protected function matchesAny(string $permission, array $userPermissions): bool
    {
        foreach ($userPermissions as $userPerm) {
            if ($this->matches($permission, $userPerm)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a permission matches a pattern.
     *
     * 'sales.invoice.view' matches:
     *   'sales.invoice.view'  (exact)
     *   'sales.invoice.*'     (wildcard action)
     *   'sales.*'             (wildcard resource)
     *   '*'                   (everything)
     */
    protected function matches(string $permission, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        $patternParts = explode('.', $pattern);
        $permParts = explode('.', $permission);

        if (count($patternParts) > count($permParts)) {
            return false;
        }

        foreach ($patternParts as $i => $part) {
            if ($part === '*') {
                return true;
            }
            if ($part !== ($permParts[$i] ?? '')) {
                return false;
            }
        }

        return count($patternParts) === count($permParts);
    }
}
