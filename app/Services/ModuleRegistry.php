<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ModuleRegistry
{
    protected static ?array $manifestCache = null;

    /**
     * Get all registered modules from filesystem.
     */
    public static function all(): array
    {
        return static::getManifest();
    }

    /**
     * Get only installed & enabled modules.
     */
    public static function enabled(): array
    {
        return array_filter(static::all(), fn($m) => $m['status'] === 'installed' && $m['is_enabled']);
    }

    /**
     * Get a single module by code.
     */
    public static function get(string $code): ?array
    {
        return static::all()[$code] ?? null;
    }

    /**
     * Check if a module exists.
     */
    public static function has(string $code): bool
    {
        return isset(static::all()[$code]);
    }

    /**
     * Check if a module is enabled.
     */
    public static function isEnabled(string $code): bool
    {
        $module = static::get($code);
        return $module && $module['is_enabled'] && $module['status'] === 'installed';
    }

    /**
     * Get all module codes.
     */
    public static function codes(): array
    {
        return array_keys(static::all());
    }

    /**
     * Get enabled module codes.
     */
    public static function enabledCodes(): array
    {
        return array_keys(static::enabled());
    }

    /**
     * Validate dependencies for a module.
     */
    public static function validateDependencies(string $code): array
    {
        $module = static::get($code);
        if (!$module) {
            return ["Module '{$code}' not found"];
        }

        $errors = [];
        $dependencies = $module['dependencies'] ?? [];

        foreach ($dependencies as $dep) {
            if (!static::has($dep)) {
                $errors[] = "Dependency '{$dep}' is not registered";
            } elseif (!static::isEnabled($dep)) {
                $errors[] = "Dependency '{$dep}' is disabled";
            }
        }

        return $errors;
    }

    /**
     * Get all permissions for enabled modules.
     */
    public static function getPermissions(): array
    {
        $permissions = [];
        foreach (static::enabled() as $code => $module) {
            $permFile = static::getModulePath($code) . '/Permissions/permissions.php';
            if (File::exists($permFile)) {
                $modulePerms = require $permFile;
                $permissions = array_merge($permissions, $modulePerms);
            }
        }
        return $permissions;
    }

    /**
     * Get menu items for enabled modules.
     */
    public static function getMenuItems(): array
    {
        $items = [];
        foreach (static::enabled() as $code => $module) {
            $menuFile = static::getModulePath($code) . '/Menu/menu.php';
            if (File::exists($menuFile)) {
                $moduleMenu = require $menuFile;
                if (is_array($moduleMenu) && isset($moduleMenu['items'])) {
                    $items = array_merge($items, $moduleMenu['items']);
                } elseif (is_array($moduleMenu) && isset($moduleMenu['key'])) {
                    $items[] = $moduleMenu;
                }
            }
        }
        return $items;
    }

    /**
     * Get features for enabled modules.
     */
    public static function getFeatures(): array
    {
        $features = [];
        foreach (static::enabled() as $code => $module) {
            $featFile = static::getModulePath($code) . '/Config/features.php';
            if (File::exists($featFile)) {
                $moduleFeatures = require $featFile;
                $features = array_merge($features, $moduleFeatures);
            }
        }
        return $features;
    }

    /**
     * Get widgets for enabled modules.
     */
    public static function getWidgets(): array
    {
        $widgets = [];
        foreach (static::enabled() as $code => $module) {
            $widgetFile = static::getModulePath($code) . '/Config/widgets.php';
            if (File::exists($widgetFile)) {
                $moduleWidgets = require $widgetFile;
                $widgets = array_merge($widgets, $moduleWidgets);
            }
        }
        return $widgets;
    }

    /**
     * Get dashboard widgets for enabled modules.
     */
    public static function getDashboardWidgets(): array
    {
        return static::getWidgets();
    }

    /**
     * Get translations for a module.
     */
    public static function getTranslations(string $code, string $locale = 'ar'): array
    {
        $langFile = static::getModulePath($code) . "/Lang/{$locale}.php";
        if (File::exists($langFile)) {
            return require $langFile;
        }
        return [];
    }

    // ─── Internal ──────────────────────────────────────

    protected static function getManifest(): array
    {
        if (static::$manifestCache !== null) {
            return static::$manifestCache;
        }

        $modulesDir = base_path('Modules');
        $manifest = [];

        if (!File::isDirectory($modulesDir)) {
            File::makeDirectory($modulesDir, 0755, true);
        }

        $directories = File::directories($modulesDir);

        foreach ($directories as $dir) {
            $jsonFile = $dir . '/module.json';
            if (!File::exists($jsonFile)) continue;

            $json = json_decode(File::get($jsonFile), true);
            if (!$json) continue;

            $code = $json['code'] ?? basename($dir);

            // Check DB status
            $dbModule = Module::withoutGlobalScopes()->where('code', $code)->first();

            $manifest[$code] = [
                'code' => $code,
                'name' => $json['name'] ?? $code,
                'name_ar' => $json['name_ar'] ?? null,
                'version' => $json['version'] ?? '1.0.0',
                'description' => $json['description'] ?? null,
                'description_ar' => $json['description_ar'] ?? null,
                'author' => $json['author'] ?? null,
                'dependencies' => $json['dependencies'] ?? [],
                'capabilities' => $json['capabilities'] ?? [],
                'path' => $dir,
                'status' => $dbModule?->status ?? 'pending',
                'is_core' => $dbModule?->is_core ?? ($json['is_core'] ?? false),
                'is_enabled' => $dbModule?->is_enabled ?? false,
                'installed_at' => $dbModule?->installed_at,
                'enabled_at' => $dbModule?->enabled_at,
                'config' => $json['config'] ?? [],
            ];
        }

        // Sort by sort_order from DB
        uasort($manifest, function ($a, $b) {
            $aOrder = Module::withoutGlobalScopes()->where('code', $a['code'])->value('sort_order') ?? 0;
            $bOrder = Module::withoutGlobalScopes()->where('code', $b['code'])->value('sort_order') ?? 0;
            return $aOrder <=> $bOrder;
        });

        static::$manifestCache = $manifest;
        return $manifest;
    }

    protected static function getModulePath(string $code): string
    {
        $module = static::get($code);
        if ($module && isset($module['path'])) {
            return $module['path'];
        }
        return base_path("Modules/{$code}");
    }

    /**
     * Clear manifest cache.
     */
    public static function clearCache(): void
    {
        static::$manifestCache = null;
        Cache::forget('module_registry');
    }
}
