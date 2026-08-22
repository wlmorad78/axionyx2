<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeePenaltyController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Penalty
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Penalty" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeePenalty;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeePenaltyController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Penalty) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeePenalty::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reason', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Penalty) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_penalty', 'store'));

        return response()->json(EmployeePenalty::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Penalty) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeePenalty $employeePenalty)
    {
        return $employeePenalty->load(['employee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Penalty) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeePenalty $employeePenalty)
    {
        $data = $request->validate(ValidationRules::for('employee_penalty', 'update', $employeePenalty));

        $employeePenalty->update($data);

        return response()->json($employeePenalty);
    }

    /**
     * حذف سجل من (Employee Penalty) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeePenalty $employeePenalty)
    {
        $employeePenalty->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Penalty) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeePenalty = EmployeePenalty::onlyTrashed()->findOrFail($id);
        $employeePenalty->restore();

        return response()->json($employeePenalty);
    }

    /**
     * حذف نهائي للسجل من (Employee Penalty) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeePenalty::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Penalty).
     */
    public function schema()
    {
        return ValidationRules::for('employee_penalty', 'store');
    }
}
