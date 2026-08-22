<?php
/**
 * =====================================================================
 * متحكم (Controller): KpiResultController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Kpi Result
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Kpi Result" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{KpiResult};
use App\Support\ValidationRules;

class KpiResultController extends Controller
{
    /**
     * عرض قائمة سجلات (Kpi Result) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = KpiResult::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('actual_value', 'like', "%{$s}%")
                  ->orWhere('achievement_percent', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Kpi Result) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('kpi_result', 'create'));
        $kpiResult = KpiResult::create($data);
        return response()->json($kpiResult, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Kpi Result) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return KpiResult::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Kpi Result) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $kpiResult = KpiResult::findOrFail($id);
        $data = $request->validate(ValidationRules::for('kpi_result', 'update', $kpiResult));
        $kpiResult->update($data);
        return $kpiResult;
    }

    /**
     * حذف سجل من (Kpi Result) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $kpiResult = KpiResult::findOrFail($id);
        $kpiResult->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Kpi Result) وإعادته للعمل.
     */
    public function restore($id)
    {
        $kpiResult = KpiResult::withTrashed()->findOrFail($id);
        $kpiResult->restore();
        return $kpiResult;
    }

    /**
     * حذف نهائي للسجل من (Kpi Result) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $kpiResult = KpiResult::withTrashed()->findOrFail($id);
        $kpiResult->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
