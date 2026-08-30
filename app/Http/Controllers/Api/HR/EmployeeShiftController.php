<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeShiftController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Shift
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Shift" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeShift;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeShiftController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Shift) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeShift::with($with);

        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->is_current !== null) $query->where('is_current', $request->boolean('is_current'));

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Shift) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_shift', 'store'));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeShift::where('user_id', $data['user_id'])
                ->where('is_current', true)->update(['is_current' => false]);
        }

        return response()->json(EmployeeShift::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Shift) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeShift $employeeShift)
    {
        return $employeeShift->load(['employee', 'shift']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Shift) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeShift $employeeShift)
    {
        $data = $request->validate(ValidationRules::for('employee_shift', 'update', $employeeShift));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeShift::where('user_id', $employeeShift->user_id)
                ->where('is_current', true)
                ->where('id', '!=', $employeeShift->id)
                ->update(['is_current' => false]);
        }

        $employeeShift->update($data);
        return response()->json($employeeShift);
    }

    /**
     * حذف سجل من (Employee Shift) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeShift $employeeShift)
    {
        $employeeShift->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Shift) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeShift = EmployeeShift::onlyTrashed()->findOrFail($id);
        $employeeShift->restore();

        return response()->json($employeeShift);
    }

    /**
     * حذف نهائي للسجل من (Employee Shift) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeShift::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Shift).
     */
    public function schema()
    {
        return ValidationRules::for('employee_shift', 'store');
    }
}
