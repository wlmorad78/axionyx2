<?php
/**
 * =====================================================================
 * متحكم (Controller): ModuleController
 * الوحدة (Module): الصلاحيات والأدوار (Permissions)
 * المورد (Resource): Module
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Module" ضمن وحدة "الصلاحيات والأدوار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\Settings\Module;
use App\Services\ModuleRegistry;
use App\Services\ModuleInstaller;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * GET /api/modules
     * List all modules from filesystem + DB.
     */
    public function index()
    {
        $modules = ModuleRegistry::all();

        return response()->json([
            'data' => array_values($modules),
            'total' => count($modules),
            'enabled' => count(ModuleRegistry::enabled()),
        ]);
    }

    /**
     * GET /api/modules/{code}
     * Get a single module details.
     */
    public function show(string $code)
    {
        $module = ModuleRegistry::get($code);
        if (!$module) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        return response()->json(['data' => $module]);
    }

    /**
     * POST /api/modules/{code}/install
     */
    public function install(string $code)
    {
        $result = ModuleInstaller::install($code);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * DELETE /api/modules/{code}/uninstall
     */
    public function uninstall(string $code)
    {
        $result = ModuleInstaller::uninstall($code);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * PUT /api/modules/{code}/enable
     */
    public function enable(string $code)
    {
        $result = ModuleInstaller::enable($code);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * PUT /api/modules/{code}/disable
     */
    public function disable(string $code)
    {
        $result = ModuleInstaller::disable($code);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /api/modules/{code}/upgrade
     */
    public function upgrade(string $code)
    {
        $result = ModuleInstaller::upgrade($code);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET /api/modules/manifest
     * Get the full module manifest (filesystem).
     */
    public function manifest()
    {
        return response()->json([
            'data' => ModuleRegistry::all(),
            'codes' => ModuleRegistry::codes(),
            'enabled' => ModuleRegistry::enabledCodes(),
        ]);
    }

    /**
     * GET /api/modules/{code}/permissions
     * Get permissions for a module.
     */
    public function permissions(string $code)
    {
        $path = base_path("Modules/{$code}/Permissions/permissions.php");
        if (!file_exists($path)) {
            return response()->json(['data' => []]);
        }

        $permissions = require $path;
        return response()->json(['data' => $permissions]);
    }

    /**
     * GET /api/modules/{code}/menu
     * Get menu items for a module.
     */
    public function menu(string $code)
    {
        $path = base_path("Modules/{$code}/Menu/menu.php");
        if (!file_exists($path)) {
            return response()->json(['data' => []]);
        }

        $menu = require $path;
        return response()->json(['data' => $menu]);
    }
}
