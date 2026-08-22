<?php
/**
 * =====================================================================
 * متحكم (Controller): OrganizationUnitTypeController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Organization Unit Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Organization Unit Type" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUnitType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OrganizationUnitTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Organization Unit Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OrganizationUnitType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->orderBy('sort_order')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Organization Unit Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('organization_unit_type', 'store'));
        $type = OrganizationUnitType::create($data);
        return response()->json($type, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Organization Unit Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(OrganizationUnitType $organizationUnitType)
    {
        return $organizationUnitType;
    }

    /**
     * تحديث بيانات سجل موجود من (Organization Unit Type) بناءً على المعرّف.
     */
    public function update(Request $request, OrganizationUnitType $organizationUnitType)
    {
        $data = $request->validate(ValidationRules::for('organization_unit_type', 'update', $organizationUnitType));
        $organizationUnitType->update($data);
        return response()->json($organizationUnitType);
    }

    /**
     * حذف سجل من (Organization Unit Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(OrganizationUnitType $organizationUnitType)
    {
        if ($organizationUnitType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع وحدة نظام'], 403);
        }
        $organizationUnitType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Organization Unit Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $type = OrganizationUnitType::onlyTrashed()->findOrFail($id);
        $type->restore();
        return response()->json($type);
    }

    /**
     * حذف نهائي للسجل من (Organization Unit Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $type = OrganizationUnitType::onlyTrashed()->findOrFail($id);
        $type->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Organization Unit Type).
     */
    public function schema()
    {
        return ValidationRules::for('organization_unit_type', 'store');
    }
}
