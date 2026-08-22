<?php
/**
 * =====================================================================
 * متحكم (Controller): ApiAuditLogController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Api Audit Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Api Audit Log" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiAuditLog;
use Illuminate\Http\Request;

class ApiAuditLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Api Audit Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApiAuditLog::query()->with('client');
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return ApiAuditLog::with('client')->findOrFail($id); }
}
