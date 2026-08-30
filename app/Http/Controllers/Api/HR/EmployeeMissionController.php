<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeMissionController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Mission
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Mission" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMission;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeMissionController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Mission) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeMission::with($with);

        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->status) $query->where('status', $request->status);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('destination', 'like', "%$s%")->orWhere('reason', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Mission) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_mission', 'store'));

        return response()->json(EmployeeMission::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Mission) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeMission $employeeMission)
    {
        return $employeeMission->load(['employee', 'approver']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Mission) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeMission $employeeMission)
    {
        $data = $request->validate(ValidationRules::for('employee_mission', 'update', $employeeMission));

        $employeeMission->update($data);

        return response()->json($employeeMission);
    }

    /**
     * حذف سجل من (Employee Mission) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeMission $employeeMission)
    {
        $employeeMission->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Mission) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeMission = EmployeeMission::onlyTrashed()->findOrFail($id);
        $employeeMission->restore();

        return response()->json($employeeMission);
    }

    /**
     * حذف نهائي للسجل من (Employee Mission) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeMission::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Mission).
     */
    public function schema()
    {
        return ValidationRules::for('employee_mission', 'store');
    }
}
