<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * GET /api/permissions
     * All permissions grouped by module with user's access status.
     */
    public function index(Request $request, PermissionService $permissionService)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $grouped = $permissionService->getPermissionsByModule();
        $userPermissions = $permissionService->getUserPermissions($user);

        $result = [];
        foreach ($grouped as $module => $permissions) {
            $result[$module] = [];
            foreach ($permissions as $perm => $description) {
                $result[$module][$perm] = [
                    'description' => $description,
                    'allowed' => in_array('*', $userPermissions) || $permissionService->check($user, $perm),
                ];
            }
        }

        return response()->json([
            'permissions' => $result,
            'user_permissions' => $userPermissions,
        ]);
    }

    /**
     * GET /api/permissions/matrix
     * Full permission matrix for the Permission Matrix UI (SAP/Odoo style).
     *
     * Returns:
     * - modules: with labels (AR/EN), icons, colors
     * - resources: with labels, grouped by module
     * - actions: with labels and icons
     * - matrix: 3D map [module][resource][action] = permission key
     * - roles: all roles with their permission keys
     * - user_permissions: current user's permission keys
     */
    public function matrix(Request $request, PermissionService $permissionService)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $config = config('permissions');
        $definitions = $config['definitions'] ?? [];
        $moduleLabels = $config['modules'] ?? [];
        $resourceLabels = $config['resources'] ?? [];
        $actionLabels = $config['actions'] ?? [];

        // Build the matrix: parse permission keys into [module][resource][action]
        $matrix = [];
        $moduleResources = [];

        foreach ($definitions as $permKey => $description) {
            $parts = explode('.', $permKey);
            if (count($parts) !== 3) continue;

            [$module, $resource, $action] = $parts;

            if (!isset($matrix[$module])) {
                $matrix[$module] = [];
            }
            if (!isset($matrix[$module][$resource])) {
                $matrix[$module][$resource] = [];
            }
            $matrix[$module][$resource][$action] = $permKey;

            if (!isset($moduleResources[$module])) {
                $moduleResources[$module] = [];
            }
            if (!in_array($resource, $moduleResources[$module])) {
                $moduleResources[$module][] = $resource;
            }
        }

        // Collect all unique actions across all resources
        $allActions = [];
        foreach ($matrix as $module => $resources) {
            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action => $key) {
                    if (!in_array($action, $allActions)) {
                        $allActions[] = $action;
                    }
                }
            }
        }
        sort($allActions);

        // Build modules with labels
        $modules = [];
        foreach ($moduleResources as $module => $resources) {
            $label = $moduleLabels[$module] ?? ['ar' => $module, 'en' => ucfirst($module)];
            $modules[$module] = [
                'label_ar' => $label['ar'],
                'label_en' => $label['en'],
                'icon' => $label['icon'] ?? 'folder',
                'color' => $label['color'] ?? '#666',
                'resources' => [],
            ];

            foreach ($resources as $resource) {
                $resLabel = $resourceLabels[$module . '.' . $resource] ?? ['ar' => $resource, 'en' => ucfirst($resource)];
                $modules[$module]['resources'][$resource] = [
                    'label_ar' => $resLabel['ar'],
                    'label_en' => $resLabel['en'],
                    'actions' => $matrix[$module][$resource] ?? [],
                ];
            }
        }

        // Build actions with labels
        $actions = [];
        foreach ($allActions as $action) {
            $label = $actionLabels[$action] ?? ['ar' => $action, 'en' => ucfirst($action)];
            $actions[$action] = [
                'label_ar' => $label['ar'],
                'label_en' => $label['en'],
                'icon' => $label['icon'] ?? 'radio_button_unchecked',
            ];
        }

        // Build roles with their permissions
        $roles = [];
        if ($request->filled('company_id')) {
            $rolesQuery = Role::where('company_id', $request->company_id)->with('permissions');
        } else {
            $rolesQuery = Role::with('permissions');
        }
        $allRoles = $rolesQuery->get();

        foreach ($allRoles as $role) {
            $rolePermKeys = $role->permissions->pluck('code')->toArray();
            $roles[$role->id] = [
                'id' => $role->id,
                'name' => $role->name,
                'code' => $role->code,
                'permissions' => $rolePermKeys,
            ];
        }

        // Current user's permissions
        $userPermissions = $permissionService->getUserPermissions($user);

        return response()->json([
            'modules' => $modules,
            'actions' => $actions,
            'all_actions' => $allActions,
            'roles' => $roles,
            'user_permissions' => $userPermissions,
            'total_permissions' => count($definitions),
        ]);
    }

    /**
     * GET /api/permissions/check/{permission}
     * Quick check: does the current user have a specific permission?
     */
    public function check(Request $request, string $permission, PermissionService $permissionService)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['allowed' => false], 401);
        }

        $allowed = $permissionService->check($user, $permission);

        return response()->json([
            'permission' => $permission,
            'allowed' => $allowed,
        ]);
    }

    /**
     * POST /api/permissions/check-batch
     * Check multiple permissions at once.
     */
    public function checkBatch(Request $request, PermissionService $permissionService)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $permissions = $request->input('permissions', []);
        $results = [];

        foreach ($permissions as $perm) {
            $results[$perm] = $permissionService->check($user, $perm);
        }

        return response()->json(['permissions' => $results]);
    }
}
