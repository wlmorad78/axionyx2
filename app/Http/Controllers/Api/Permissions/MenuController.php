<?php
/**
 * =====================================================================
 * متحكم (Controller): MenuController
 * الوحدة (Module): الصلاحيات والأدوار (Permissions)
 * المورد (Resource): Menu
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Menu" ضمن وحدة "الصلاحيات والأدوار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * GET /api/menu/sidebar
     * Returns the filtered sidebar menu for the authenticated user.
     */
    public function sidebar(Request $request, MenuService $menuService)
    {
        $companyId = $request->header('X-Company-Id') ?? $request->query('company_id');
        $menu = $menuService->buildMenu($request->user(), $companyId ? (int) $companyId : null);

        return response()->json([
            'data' => $menu,
        ]);
    }
}
