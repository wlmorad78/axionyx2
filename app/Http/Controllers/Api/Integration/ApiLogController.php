<?php
/**
 * =====================================================================
 * متحكم (Controller): ApiLogController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Api Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Api Log" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Api Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApiLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('endpoint', 'like', "%{$s}%")
                    ->orWhere('method', 'like', "%{$s}%")
                    ->orWhere('status_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Api Log) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_log', 'create'));
        $apiLog = ApiLog::create($data);
        return response()->json($apiLog, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Api Log) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ApiLog::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Api Log) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $apiLog = ApiLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_log', 'update', $apiLog));
        $apiLog->update($data);
        return $apiLog;
    }

    /**
     * حذف سجل من (Api Log) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $apiLog = ApiLog::findOrFail($id);
        $apiLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Api Log) وإعادته للعمل.
     */
    public function restore($id)
    {
        $apiLog = ApiLog::withTrashed()->findOrFail($id);
        $apiLog->restore();
        return $apiLog;
    }

    /**
     * حذف نهائي للسجل من (Api Log) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $apiLog = ApiLog::withTrashed()->findOrFail($id);
        $apiLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
