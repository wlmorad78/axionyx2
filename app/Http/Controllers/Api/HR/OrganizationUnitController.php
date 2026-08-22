<?php
/**
 * =====================================================================
 * متحكم (Controller): OrganizationUnitController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Organization Unit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Organization Unit" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUnit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OrganizationUnitController extends Controller
{
    /**
     * عرض قائمة سجلات (Organization Unit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OrganizationUnit::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->organization_unit_type_id) {
            $query->where('organization_unit_type_id', $request->organization_unit_type_id);
        }
        if ($request->organizational_level_id) {
            $query->where('organizational_level_id', $request->organizational_level_id);
        }
        if ($request->parent_id !== null) {
            $query->where('parent_id', $request->parent_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Organization Unit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('organization_unit', 'store'));
        $unit = OrganizationUnit::create($data);
        return response()->json($unit, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Organization Unit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(OrganizationUnit $organizationUnit)
    {
        return $organizationUnit->load(['company', 'unitType', 'parent', 'organizationalLevel', 'branch', 'children']);
    }

    /**
     * تحديث بيانات سجل موجود من (Organization Unit) بناءً على المعرّف.
     */
    public function update(Request $request, OrganizationUnit $organizationUnit)
    {
        $data = $request->validate(ValidationRules::for('organization_unit', 'update', $organizationUnit));
        $organizationUnit->update($data);
        return response()->json($organizationUnit);
    }

    /**
     * حذف سجل من (Organization Unit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(OrganizationUnit $organizationUnit)
    {
        $organizationUnit->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Organization Unit) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $unit = OrganizationUnit::onlyTrashed()->findOrFail($id);
        $unit->restore();
        return response()->json($unit);
    }

    /**
     * حذف نهائي للسجل من (Organization Unit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $unit = OrganizationUnit::onlyTrashed()->findOrFail($id);
        $unit->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Organization Unit).
     */
    public function nextCode(Request $request)
    {
        $last = OrganizationUnit::orderBy('id', 'desc')->first();
        if ($last && preg_match('/OU-(\d+)/', $last->code, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'OU-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Organization Unit).
     */
    public function schema()
    {
        return ValidationRules::for('organization_unit', 'store');
    }
}
