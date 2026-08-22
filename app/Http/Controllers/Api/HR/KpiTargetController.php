<?php
/**
 * =====================================================================
 * متحكم (Controller): KpiTargetController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Kpi Target
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Kpi Target" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{KpiTarget};
use App\Support\ValidationRules;

class KpiTargetController extends Controller
{
    /**
     * عرض قائمة سجلات (Kpi Target) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = KpiTarget::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('target_value', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Kpi Target) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('kpi_target', 'create'));
        $kpiTarget = KpiTarget::create($data);
        return response()->json($kpiTarget, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Kpi Target) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return KpiTarget::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Kpi Target) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $kpiTarget = KpiTarget::findOrFail($id);
        $data = $request->validate(ValidationRules::for('kpi_target', 'update', $kpiTarget));
        $kpiTarget->update($data);
        return $kpiTarget;
    }

    /**
     * حذف سجل من (Kpi Target) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $kpiTarget = KpiTarget::findOrFail($id);
        $kpiTarget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Kpi Target) وإعادته للعمل.
     */
    public function restore($id)
    {
        $kpiTarget = KpiTarget::withTrashed()->findOrFail($id);
        $kpiTarget->restore();
        return $kpiTarget;
    }

    /**
     * حذف نهائي للسجل من (Kpi Target) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $kpiTarget = KpiTarget::withTrashed()->findOrFail($id);
        $kpiTarget->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
