<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions');
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        return response()->json([
            'data' => $query->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'code' => $role->code,
                    'description' => $role->description,
                    'is_global' => $role->is_global ?? false,
                    'is_system' => $role->is_system ?? false,
                    'company_id' => $role->company_id,
                    'permissions' => $role->permissions->pluck('code')->toArray(),
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('role', 'store'));
        $role = Role::create($data);

        // Support both permission_ids and permission_codes
        $this->syncRolePermissions($role, $request);

        return response()->json($role->load(['permissions']), 201);
    }

    public function show(Role $role)
    {
        return $role->load(['permissions']);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate(ValidationRules::for('role', 'update', $role));
        $role->update($data);

        // Support both permission_ids and permission_codes
        $this->syncRolePermissions($role, $request);

        return response()->json($role->load(['permissions']));
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(null, 204);
    }

    /**
     * POST /api/roles/{role}/permissions
     * Bulk update permissions for a role.
     *
     * Request body:
     * - permissions: ["sales.invoice.view", "sales.invoice.create", ...]
     */
    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        $permCodes = $request->input('permissions', []);

        // Resolve wildcard permissions to actual permission records
        $allPerms = Permission::all();
        $resolved = collect();

        foreach ($allPerms as $perm) {
            foreach ($permCodes as $pattern) {
                if ($this->matchesPermission($perm->code, $pattern)) {
                    $resolved->push($perm->id);
                    break;
                }
            }
        }

        $role->permissions()->sync($resolved->unique());

        return response()->json([
            'role' => $role->load('permissions'),
            'assigned_count' => $resolved->unique()->count(),
        ]);
    }

    /**
     * POST /api/roles/copy-permissions
     * Copy permissions from one role to another.
     */
    public function copyPermissions(Request $request)
    {
        $request->validate([
            'source_role_id' => 'required|exists:roles,id',
            'target_role_id' => 'required|exists:roles,id',
        ]);

        $source = Role::with('permissions')->findOrFail($request->source_role_id);
        $target = Role::findOrFail($request->target_role_id);

        $target->permissions()->sync($source->permissions->pluck('id'));

        return response()->json([
            'target' => $target->load('permissions'),
            'copied_count' => $source->permissions->count(),
        ]);
    }

    public function schema()
    {
        return ValidationRules::for('role', 'store');
    }

    private function syncRolePermissions(Role $role, Request $request): void
    {
        if ($request->has('permission_ids')) {
            $role->syncPermissions($request->permission_ids);
        } elseif ($request->has('permissions')) {
            $permCodes = $request->input('permissions', []);
            $allPerms = Permission::all();
            $resolved = collect();

            foreach ($allPerms as $perm) {
                foreach ($permCodes as $pattern) {
                    if ($this->matchesPermission($perm->code, $pattern)) {
                        $resolved->push($perm->id);
                        break;
                    }
                }
            }

            $role->permissions()->sync($resolved->unique());
        }
    }

    private function matchesPermission(string $permission, string $pattern): bool
    {
        if ($pattern === '*') return true;

        $patternParts = explode('.', $pattern);
        $permParts = explode('.', $permission);

        if (count($patternParts) > count($permParts)) return false;

        foreach ($patternParts as $i => $part) {
            if ($part === '*') return true;
            if ($part !== ($permParts[$i] ?? '')) return false;
        }

        return count($patternParts) === count($permParts);
    }
}
