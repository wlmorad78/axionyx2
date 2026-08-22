<?php
/**
 * =====================================================================
 * متحكم (Controller): KpiDefinitionController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Kpi Definition
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Kpi Definition" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{KpiDefinition};
use App\Support\ValidationRules;

class KpiDefinitionController extends Controller
{
    /**
     * عرض قائمة سجلات (Kpi Definition) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = KpiDefinition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('kpi_code', 'like', "%{$s}%")
                  ->orWhere('kpi_name', 'like', "%{$s}%")
                  ->orWhere('module', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Kpi Definition) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('kpi_definition', 'create'));
        $kpiDefinition = KpiDefinition::create($data);
        return response()->json($kpiDefinition, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Kpi Definition) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return KpiDefinition::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Kpi Definition) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $kpiDefinition = KpiDefinition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('kpi_definition', 'update', $kpiDefinition));
        $kpiDefinition->update($data);
        return $kpiDefinition;
    }

    /**
     * حذف سجل من (Kpi Definition) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $kpiDefinition = KpiDefinition::findOrFail($id);
        $kpiDefinition->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Kpi Definition) وإعادته للعمل.
     */
    public function restore($id)
    {
        $kpiDefinition = KpiDefinition::withTrashed()->findOrFail($id);
        $kpiDefinition->restore();
        return $kpiDefinition;
    }

    /**
     * حذف نهائي للسجل من (Kpi Definition) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $kpiDefinition = KpiDefinition::withTrashed()->findOrFail($id);
        $kpiDefinition->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
