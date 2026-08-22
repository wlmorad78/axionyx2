<?php
/**
 * =====================================================================
 * متحكم (Controller): ApiPermissionController
 * الوحدة (Module): الصلاحيات والأدوار (Permissions)
 * المورد (Resource): Api Permission
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Api Permission" ضمن وحدة "الصلاحيات والأدوار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\ApiPermission;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiPermissionController extends Controller
{
    /**
     * عرض قائمة سجلات (Api Permission) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApiPermission::query()->with('client');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('resource_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Api Permission) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_permission', 'create'));
        return response()->json(ApiPermission::create($data), 201);
    }

    public function show($id) { return ApiPermission::with('client')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = ApiPermission::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_permission', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { ApiPermission::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
