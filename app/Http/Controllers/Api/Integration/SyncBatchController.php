<?php
/**
 * =====================================================================
 * متحكم (Controller): SyncBatchController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Sync Batch
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sync Batch" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SyncBatch};
use App\Support\ValidationRules;

class SyncBatchController extends Controller
{
    /**
     * عرض قائمة سجلات (Sync Batch) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SyncBatch::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('device_id', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Sync Batch) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sync_batch', 'create'));
        $syncBatch = SyncBatch::create($data);
        return response()->json($syncBatch, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sync Batch) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return SyncBatch::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Sync Batch) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $syncBatch = SyncBatch::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sync_batch', 'update', $syncBatch));
        $syncBatch->update($data);
        return $syncBatch;
    }

    /**
     * حذف سجل من (Sync Batch) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $syncBatch = SyncBatch::findOrFail($id);
        $syncBatch->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sync Batch) وإعادته للعمل.
     */
    public function restore($id)
    {
        $syncBatch = SyncBatch::withTrashed()->findOrFail($id);
        $syncBatch->restore();
        return $syncBatch;
    }

    /**
     * حذف نهائي للسجل من (Sync Batch) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $syncBatch = SyncBatch::withTrashed()->findOrFail($id);
        $syncBatch->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
