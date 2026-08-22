<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeSalaryStructureController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Salary Structure
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Salary Structure" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalaryStructure;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeSalaryStructureController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Salary Structure) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeSalaryStructure::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->salary_component_id) {
            $query->where('salary_component_id', $request->salary_component_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Salary Structure) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_salary_structure', 'store'));

        if (isset($data['is_current']) && $data['is_current']) {
            EmployeeSalaryStructure::where('employee_id', $data['employee_id'])
                ->where('salary_component_id', $data['salary_component_id'])
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        return response()->json(EmployeeSalaryStructure::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Salary Structure) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeSalaryStructure $employeeSalaryStructure)
    {
        return $employeeSalaryStructure->load(['employee', 'salaryComponent']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Salary Structure) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeSalaryStructure $employeeSalaryStructure)
    {
        $data = $request->validate(ValidationRules::for('employee_salary_structure', 'update', $employeeSalaryStructure));

        if (isset($data['is_current']) && $data['is_current']) {
            EmployeeSalaryStructure::where('employee_id', $employeeSalaryStructure->employee_id)
                ->where('salary_component_id', $employeeSalaryStructure->salary_component_id)
                ->where('is_current', true)
                ->where('id', '!=', $employeeSalaryStructure->id)
                ->update(['is_current' => false]);
        }

        $employeeSalaryStructure->update($data);

        return response()->json($employeeSalaryStructure);
    }

    /**
     * حذف سجل من (Employee Salary Structure) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeSalaryStructure $employeeSalaryStructure)
    {
        $employeeSalaryStructure->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Salary Structure) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeSalaryStructure = EmployeeSalaryStructure::onlyTrashed()->findOrFail($id);

        $employeeSalaryStructure->restore();

        return response()->json($employeeSalaryStructure);
    }

    /**
     * حذف نهائي للسجل من (Employee Salary Structure) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeSalaryStructure::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Salary Structure).
     */
    public function schema()
    {
        return ValidationRules::for('employee_salary_structure', 'store');
    }
}
