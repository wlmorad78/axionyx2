<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanySidebarSetting;

class MenuService
{
    protected PermissionService $permissionService;

    public function __construct(?PermissionService $permissionService = null)
    {
        $this->permissionService = $permissionService ?? new PermissionService();
    }

    public function buildMenu(User $user, ?int $companyId = null): array
    {
        $menuConfig = config('menu.items', []);

        $moduleMenus = ModuleRegistry::getMenuItems();
        $menuConfig = $this->mergeMenuItems($menuConfig, $moduleMenus);

        $result = [];
        foreach ($menuConfig as $item) {
            if (!is_array($item) || empty($item['key'])) continue;

            $mapped = [
                'key' => $item['key'],
                'permission' => $item['permission'] ?? null,
                'title_en' => $item['title_en'] ?? $item['title_ar'] ?? $item['key'],
                'title_ar' => $item['title_ar'] ?? $item['title_en'] ?? $item['key'],
                'icon' => $item['icon'] ?? 'circle',
                'color' => $item['color'] ?? '#6B7280',
                'order' => $item['order'] ?? $item['sort_order'] ?? 0,
            ];

            if (!empty($item['children']) && is_array($item['children'])) {
                $mapped['children'] = [];
                foreach ($item['children'] as $child) {
                    if (!is_array($child) || empty($child['key'])) continue;
                    $mapped['children'][] = [
                        'key' => $child['key'],
                        'permission' => $child['permission'] ?? null,
                        'title_en' => $child['title_en'] ?? $child['title_ar'] ?? $child['key'],
                        'title_ar' => $child['title_ar'] ?? $child['title_en'] ?? $child['key'],
                        'icon' => $child['icon'] ?? 'circle',
                        'color' => $child['color'] ?? '#6B7280',
                        'order' => $child['order'] ?? $child['sort_order'] ?? 0,
                    ];
                }
            }

            $result[] = $mapped;
        }

        $result = $this->filterByPermissions($result, $user);

        if ($companyId) {
            $result = $this->filterByCompanySettings($result, $companyId);
        }

        return $result;
    }

    protected function filterByPermissions(array $menu, User $user): array
    {
        $result = [];
        $isAdmin = $user->isAdmin();

        foreach ($menu as $item) {
            $permission = $item['permission'] ?? null;

            // Items with null permission are hidden from non-admin users
            if (!$permission && !$isAdmin) {
                continue;
            }

            if ($permission && !$this->permissionService->check($user, $permission)) {
                continue;
            }

            if (!empty($item['children']) && is_array($item['children'])) {
                $filteredChildren = [];
                foreach ($item['children'] as $child) {
                    $childPermission = $child['permission'] ?? null;

                    // Items with null permission are hidden from non-admin users
                    if (!$childPermission && !$isAdmin) {
                        continue;
                    }

                    if ($childPermission && !$this->permissionService->check($user, $childPermission)) {
                        continue;
                    }
                    $filteredChildren[] = $child;
                }
                $item['children'] = $filteredChildren;
            }

            $result[] = $item;
        }

        return $result;
    }

    protected function filterByCompanySettings(array $menu, int $companyId): array
    {
        $settings = CompanySidebarSetting::where('company_id', $companyId)
            ->pluck('is_visible', 'menu_key')
            ->toArray();

        if (empty($settings)) {
            return $menu;
        }

        $result = [];
        foreach ($menu as $item) {
            $key = $item['key'] ?? null;

            if ($key && array_key_exists($key, $settings) && !$settings[$key]) {
                continue;
            }

            if (!empty($item['children']) && is_array($item['children'])) {
                $filteredChildren = [];
                foreach ($item['children'] as $child) {
                    $childKey = $child['key'] ?? null;
                    if ($childKey && array_key_exists($childKey, $settings) && !$settings[$childKey]) {
                        continue;
                    }
                    $filteredChildren[] = $child;
                }
                $item['children'] = $filteredChildren;
            }

            $result[] = $item;
        }

        return $result;
    }

    protected function mergeMenuItems(array $base, array $modules): array
    {
        foreach ($modules as $moduleItem) {
            if (!is_array($moduleItem) || empty($moduleItem['key'])) continue;

            $key = $moduleItem['key'];
            $existingIndex = null;
            foreach ($base as $i => $item) {
                if (is_array($item) && ($item['key'] ?? null) === $key) {
                    $existingIndex = $i;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $existingChildren = $base[$existingIndex]['children'] ?? [];
                $newChildren = $moduleItem['children'] ?? [];
                $base[$existingIndex]['children'] = $this->mergeChildren($existingChildren, $newChildren);
            } else {
                $base[] = $moduleItem;
            }
        }

        return $base;
    }

    protected function mergeChildren(array $existing, array $new): array
    {
        $result = $existing;

        foreach ($new as $child) {
            if (!is_array($child) || empty($child['key'])) continue;

            $childKey = $child['key'];
            $found = false;
            foreach ($result as $i => $item) {
                if (is_array($item) && ($item['key'] ?? null) === $childKey) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $result[] = $child;
            }
        }

        return $result;
    }
}
