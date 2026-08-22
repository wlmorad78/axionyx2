<?php
/**
 * =====================================================================
 * متحكم (Controller): SalaryComponentTypeController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Salary Component Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salary Component Type" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponentType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalaryComponentTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Salary Component Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalaryComponentType::with($with);

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
     * إنشاء سجل جديد لـ (Salary Component Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salary_component_type', 'store'));

        return response()->json(SalaryComponentType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salary Component Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalaryComponentType $salaryComponentType)
    {
        return $salaryComponentType;
    }

    /**
     * تحديث بيانات سجل موجود من (Salary Component Type) بناءً على المعرّف.
     */
    public function update(Request $request, SalaryComponentType $salaryComponentType)
    {
        $data = $request->validate(ValidationRules::for('salary_component_type', 'update', $salaryComponentType));

        $salaryComponentType->update($data);

        return response()->json($salaryComponentType);
    }

    /**
     * حذف سجل من (Salary Component Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalaryComponentType $salaryComponentType)
    {
        $salaryComponentType->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Salary Component Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $salaryComponentType = SalaryComponentType::onlyTrashed()->findOrFail($id);

        $salaryComponentType->restore();

        return response()->json($salaryComponentType);
    }

    /**
     * حذف نهائي للسجل من (Salary Component Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalaryComponentType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salary Component Type).
     */
    public function schema()
    {
        return ValidationRules::for('salary_component_type', 'store');
    }
}
