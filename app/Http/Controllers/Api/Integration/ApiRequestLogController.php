<?php
/**
 * =====================================================================
 * متحكم (Controller): ApiRequestLogController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Api Request Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Api Request Log" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use Illuminate\Http\Request;

class ApiRequestLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Api Request Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApiRequestLog::query()->with('client');
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return ApiRequestLog::with('client')->findOrFail($id); }
}
