<?php
/**
 * =====================================================================
 * متحكم (Controller): SyncLogController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Sync Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sync Log" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SyncLog};
use App\Support\ValidationRules;

class SyncLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Sync Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SyncLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('table_name', 'like', "%{$s}%")
                  ->orWhere('operation', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Sync Log) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sync_log', 'create'));
        $syncLog = SyncLog::create($data);
        return response()->json($syncLog, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sync Log) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return SyncLog::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Sync Log) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $syncLog = SyncLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sync_log', 'update', $syncLog));
        $syncLog->update($data);
        return $syncLog;
    }

    /**
     * حذف سجل من (Sync Log) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $syncLog = SyncLog::findOrFail($id);
        $syncLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sync Log) وإعادته للعمل.
     */
    public function restore($id)
    {
        $syncLog = SyncLog::withTrashed()->findOrFail($id);
        $syncLog->restore();
        return $syncLog;
    }

    /**
     * حذف نهائي للسجل من (Sync Log) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $syncLog = SyncLog::withTrashed()->findOrFail($id);
        $syncLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
