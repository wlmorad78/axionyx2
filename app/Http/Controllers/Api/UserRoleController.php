<?php
/**
 * =====================================================================
 * متحكم (Controller): UserRoleController
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): User Role
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "User Role" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * عرض قائمة سجلات (User Role) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);
        $roles = $user->roles()->get(['roles.id', 'roles.name', 'roles.code', 'roles.description']);

        return response()->json([
            'user_id' => $user->id,
            'roles' => $roles,
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (User Role) بناءً على المعرّف.
     */
    public function update(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $user->roles()->sync($request->role_ids);

        $roles = $user->roles()->get(['roles.id', 'roles.name', 'roles.code', 'roles.description']);

        return response()->json([
            'user_id' => $user->id,
            'roles' => $roles,
        ]);
    }
}
