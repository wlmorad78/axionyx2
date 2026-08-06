<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Feature;
use App\Models\DashboardWidget;
use App\Models\Permission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ModuleInstaller
{
    /**
     * Install a module from its module.json.
     */
    public static function install(string $code): array
    {
        $moduleData = ModuleRegistry::get($code);
        if (!$moduleData) {
            return ['success' => false, 'message' => "Module '{$code}' not found in Modules/ directory"];
        }

        // Check dependencies
        $depErrors = ModuleRegistry::validateDependencies($code);
        if (!empty($depErrors)) {
            return ['success' => false, 'message' => 'Dependencies not met', 'errors' => $depErrors];
        }

        $path = $moduleData['path'];

        DB::beginTransaction();

        try {
            // 1. Run migrations
            static::runMigrations($path);

            // 2. Register permissions
            static::registerPermissions($code, $path);

            // 3. Register features
            static::registerFeatures($code, $path);

            // 4. Register menu items
            // (Menu is config-driven, no DB registration needed)

            // 5. Register widgets
            static::registerWidgets($code, $path);

            // 6. Register settings
            static::registerSettings($code, $path);

            // 7. Run seeders
            static::runSeeders($path);

            // 8. Create/update module record
            $module = Module::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $moduleData['name'],
                    'name_ar' => $moduleData['name_ar'],
                    'version' => $moduleData['version'],
                    'status' => 'installed',
                    'is_core' => $moduleData['is_core'],
                    'is_enabled' => true,
                    'dependencies' => $moduleData['dependencies'],
                    'capabilities' => $moduleData['capabilities'],
                    'description' => $moduleData['description'],
                    'description_ar' => $moduleData['description_ar'],
                    'author' => $moduleData['author'],
                    'path' => $path,
                    'installed_at' => now(),
                    'enabled_at' => now(),
                ]
            );

            // 9. Register service provider if exists
            static::registerProvider($path);

            DB::commit();

            ModuleRegistry::clearCache();

            return ['success' => true, 'message' => "Module '{$code}' installed successfully", 'module' => $module];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Module install failed: {$code}", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Uninstall a module.
     */
    public static function uninstall(string $code): array
    {
        $module = Module::where('code', $code)->first();
        if (!$module) {
            return ['success' => false, 'message' => "Module '{$code}' not installed"];
        }

        if ($module->is_core) {
            return ['success' => false, 'message' => "Cannot uninstall core module '{$code}'"];
        }

        // Check if other modules depend on this
        $dependents = Module::where('dependencies', 'LIKE', "%\"{$code}\"%")->get();
        if ($dependents->isNotEmpty()) {
            $names = $dependents->pluck('code')->implode(', ');
            return ['success' => false, 'message' => "Cannot uninstall: modules [{$names}] depend on this module"];
        }

        DB::beginTransaction();

        try {
            $path = $module->path ?? base_path("Modules/{$code}");

            // Reverse: remove features, permissions, widgets
            static::removeFeatures($code);
            static::removePermissions($code);
            static::removeWidgets($code);

            // Run down migrations if available
            static::rollbackMigrations($path);

            // Delete module record
            $module->delete();

            DB::commit();

            ModuleRegistry::clearCache();

            return ['success' => true, 'message' => "Module '{$code}' uninstalled"];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Enable a module.
     */
    public static function enable(string $code): array
    {
        $module = Module::where('code', $code)->first();
        if (!$module) {
            return ['success' => false, 'message' => "Module '{$code}' not found"];
        }

        // Validate dependencies
        $depErrors = ModuleRegistry::validateDependencies($code);
        if (!empty($depErrors)) {
            return ['success' => false, 'message' => 'Dependencies not met', 'errors' => $depErrors];
        }

        $module->update(['is_enabled' => true, 'enabled_at' => now()]);
        ModuleRegistry::clearCache();

        return ['success' => true, 'message' => "Module '{$code}' enabled"];
    }

    /**
     * Disable a module.
     */
    public static function disable(string $code): array
    {
        $module = Module::where('code', $code)->first();
        if (!$module) {
            return ['success' => false, 'message' => "Module '{$code}' not found"];
        }

        if ($module->is_core) {
            return ['success' => false, 'message' => "Cannot disable core module '{$code}'"];
        }

        // Check dependents
        $dependents = Module::enabled()->where('dependencies', 'LIKE', "%\"{$code}\"%")->get();
        if ($dependents->isNotEmpty()) {
            $names = $dependents->pluck('code')->implode(', ');
            return ['success' => false, 'message' => "Cannot disable: modules [{$names}] depend on this module"];
        }

        $module->update(['is_enabled' => false, 'enabled_at' => null]);
        ModuleRegistry::clearCache();

        return ['success' => true, 'message' => "Module '{$code}' disabled"];
    }

    /**
     * Upgrade a module to a new version.
     */
    public static function upgrade(string $code): array
    {
        $moduleData = ModuleRegistry::get($code);
        $dbModule = Module::where('code', $code)->first();

        if (!$moduleData || !$dbModule) {
            return ['success' => false, 'message' => "Module '{$code}' not found"];
        }

        if (version_compare($moduleData['version'], $dbModule->version, '<=')) {
            return ['success' => false, 'message' => "Module '{$code}' is already at latest version ({$dbModule->version})"];
        }

        $path = $moduleData['path'];

        DB::beginTransaction();

        try {
            // Run upgrade migrations
            $upgradePath = $path . '/Migrations/Upgrades';
            if (File::isDirectory($upgradePath)) {
                Artisan::call('migrate', ['--path' => $upgradePath, '--force' => true]);
            }

            // Run upgrade seeder if exists
            $upgradeSeeder = $path . '/Seeders/UpgradeSeeder.php';
            if (File::exists($upgradeSeeder)) {
                Artisan::call('db:seed', ['--class' => basename($upgradeSeeder, '.php'), '--force' => true]);
            }

            $dbModule->update(['version' => $moduleData['version']]);

            DB::commit();
            ModuleRegistry::clearCache();

            return ['success' => true, 'message' => "Module '{$code}' upgraded to {$moduleData['version']}"];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Internal Methods ──────────────────────────────

    protected static function runMigrations(string $path): void
    {
        $migrationsPath = $path . '/Migrations';
        if (File::isDirectory($migrationsPath)) {
            Artisan::call('migrate', ['--path' => $migrationsPath, '--force' => true]);
        }
    }

    protected static function rollbackMigrations(string $path): void
    {
        $migrationsPath = $path . '/Migrations';
        if (File::isDirectory($migrationsPath)) {
            Artisan::call('migrate:rollback', ['--path' => $migrationsPath, '--force' => true]);
        }
    }

    protected static function runSeeders(string $path): void
    {
        $seedersPath = $path . '/Seeders';
        if (File::isDirectory($seedersPath)) {
            $files = File::glob($seedersPath . '/*.php');
            foreach ($files as $file) {
                $class = basename($file, '.php');
                try {
                    Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
                } catch (\Exception $e) {
                    Log::warning("Seeder {$class} skipped: " . $e->getMessage());
                }
            }
        }
    }

    protected static function registerPermissions(string $code, string $path): void
    {
        $permFile = $path . '/Permissions/permissions.php';
        if (!File::exists($permFile)) return;

        $permissions = require $permFile;
        foreach ($permissions as $perm) {
            // Support both formats: ['code' => '...', 'label' => '...'] and plain strings
            if (is_string($perm)) {
                Permission::updateOrCreate(
                    ['code' => $perm],
                    ['name' => $perm]
                );
            } elseif (is_array($perm) && isset($perm['code'])) {
                Permission::updateOrCreate(
                    ['code' => $perm['code']],
                    ['name' => $perm['label'] ?? $perm['code']]
                );
            }
        }
    }

    protected static function removePermissions(string $code): void
    {
        Permission::where('module', $code)->delete();
    }

    protected static function registerFeatures(string $code, string $path): void
    {
        $featFile = $path . '/Config/features.php';
        if (!File::exists($featFile)) return;

        $features = require $featFile;
        foreach ($features as $key => $feat) {
            // Support both formats: ['code' => '...'] and key => [...]
            if (is_string($feat)) continue;

            $featCode = $feat['code'] ?? $key;
            Feature::updateOrCreate(
                ['code' => $featCode],
                [
                    'name' => $feat['name'] ?? $featCode,
                    'name_ar' => $feat['name_ar'] ?? $feat['name'] ?? null,
                    'category' => $feat['category'] ?? $code,
                    'sort_order' => $feat['sort_order'] ?? 0,
                ]
            );
        }
    }

    protected static function removeFeatures(string $code): void
    {
        Feature::where('category', $code)->delete();
    }

    protected static function registerWidgets(string $code, string $path): void
    {
        $widgetFile = $path . '/Config/widgets.php';
        if (!File::exists($widgetFile)) return;

        $widgets = require $widgetFile;
        foreach ($widgets as $key => $w) {
            if (is_string($w)) continue;

            $widgetCode = $w['code'] ?? $key;
            DashboardWidget::updateOrCreate(
                ['code' => $widgetCode],
                [
                    'name' => $w['name'] ?? $w['name_en'] ?? $widgetCode,
                    'name_ar' => $w['name_ar'] ?? $w['name'] ?? null,
                    'category' => $w['category'] ?? $code,
                    'widget_type' => $w['widget_type'] ?? 'card',
                    'default_sort_order' => $w['sort_order'] ?? 0,
                    'default_width' => $w['width'] ?? 1,
                ]
            );
        }
    }

    protected static function removeWidgets(string $code): void
    {
        DashboardWidget::where('category', $code)->delete();
    }

    protected static function registerSettings(string $code, string $path): void
    {
        $settingsFile = $path . '/Config/settings.php';
        if (!File::exists($settingsFile)) return;

        // Settings are config-driven, no DB registration needed per se
        // but we log them for the settings UI
    }

    protected static function registerProvider(string $path): void
    {
        $providerFile = $path . '/Providers/ModuleServiceProvider.php';
        if (!File::exists($providerFile)) return;

        try {
            $providerClass = require $providerFile;
            if (is_string($providerClass) && class_exists($providerClass)) {
                app()->register($providerClass);
            }
        } catch (\Exception $e) {
            Log::warning("Service provider registration failed: " . $e->getMessage());
        }
    }
}
