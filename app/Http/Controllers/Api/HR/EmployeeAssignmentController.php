<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeAssignmentController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Assignment" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeAssignment::with($with);

        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->job_title_id) $query->where('job_title_id', $request->job_title_id);
        if ($request->is_current !== null) $query->where('is_current', $request->boolean('is_current'));
        if ($request->trashed) $query->onlyTrashed();

        return $query->orderBy('effective_from', 'desc')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_assignment', 'store'));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeAssignment::where('employee_id', $data['employee_id'])
                ->where('is_current', true)->update(['is_current' => false]);
        }

        return response()->json(EmployeeAssignment::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeAssignment $employeeAssignment)
    {
        return $employeeAssignment->load([
            'employee', 'branch', 'organizationUnit', 'costCenter', 'salesTerritory',
            'jobTitle', 'jobGrade', 'salaryScale', 'directManager',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeAssignment $employeeAssignment)
    {
        $data = $request->validate(ValidationRules::for('employee_assignment', 'update', $employeeAssignment));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeAssignment::where('employee_id', $employeeAssignment->employee_id)
                ->where('is_current', true)
                ->where('id', '!=', $employeeAssignment->id)
                ->update(['is_current' => false]);
        }

        $employeeAssignment->update($data);
        return response()->json($employeeAssignment);
    }

    public function destroy(EmployeeAssignment $employeeAssignment) { $employeeAssignment->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $a = EmployeeAssignment::onlyTrashed()->findOrFail($id); $a->restore(); return response()->json($a); }
    public function forceDelete(int $id) { EmployeeAssignment::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function schema() { return ValidationRules::for('employee_assignment', 'store'); }
}
