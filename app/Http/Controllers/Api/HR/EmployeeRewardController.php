<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeRewardController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Reward
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Reward" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeReward;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeRewardController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Reward) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeReward::with($with);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
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
     * إنشاء سجل جديد لـ (Employee Reward) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_reward', 'store'));

        return response()->json(EmployeeReward::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Reward) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeReward $employeeReward)
    {
        return $employeeReward->load(['employee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Reward) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeReward $employeeReward)
    {
        $data = $request->validate(ValidationRules::for('employee_reward', 'update', $employeeReward));

        $employeeReward->update($data);

        return response()->json($employeeReward);
    }

    /**
     * حذف سجل من (Employee Reward) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeReward $employeeReward)
    {
        $employeeReward->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Reward) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeReward = EmployeeReward::onlyTrashed()->findOrFail($id);
        $employeeReward->restore();

        return response()->json($employeeReward);
    }

    /**
     * حذف نهائي للسجل من (Employee Reward) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeReward::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Reward).
     */
    public function schema()
    {
        return ValidationRules::for('employee_reward', 'store');
    }
}
