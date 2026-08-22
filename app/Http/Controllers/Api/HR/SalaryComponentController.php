<?php
/**
 * =====================================================================
 * متحكم (Controller): SalaryComponentController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Salary Component
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salary Component" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    /**
     * عرض قائمة سجلات (Salary Component) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalaryComponent::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->salary_component_type_id) {
            $query->where('salary_component_type_id', $request->salary_component_type_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Salary Component) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salary_component', 'store'));

        return response()->json(SalaryComponent::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salary Component) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalaryComponent $salaryComponent)
    {
        return $salaryComponent->load(['company', 'salaryComponentType']);
    }

    /**
     * تحديث بيانات سجل موجود من (Salary Component) بناءً على المعرّف.
     */
    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $data = $request->validate(ValidationRules::for('salary_component', 'update', $salaryComponent));

        $salaryComponent->update($data);

        return response()->json($salaryComponent);
    }

    /**
     * حذف سجل من (Salary Component) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Salary Component) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $salaryComponent = SalaryComponent::onlyTrashed()->findOrFail($id);

        $salaryComponent->restore();

        return response()->json($salaryComponent);
    }

    /**
     * حذف نهائي للسجل من (Salary Component) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalaryComponent::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salary Component).
     */
    public function schema()
    {
        return ValidationRules::for('salary_component', 'store');
    }
}
