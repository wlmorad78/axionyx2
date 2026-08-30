<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeAdvanceController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Advance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Advance" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAdvance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeAdvanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Advance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeAdvance::with($with);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('advance_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Advance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_advance', 'store'));

        return response()->json(EmployeeAdvance::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Advance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeAdvance $employeeAdvance)
    {
        return $employeeAdvance->load(['employee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Advance) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeAdvance $employeeAdvance)
    {
        $data = $request->validate(ValidationRules::for('employee_advance', 'update', $employeeAdvance));

        $employeeAdvance->update($data);

        return response()->json($employeeAdvance);
    }

    /**
     * حذف سجل من (Employee Advance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeAdvance $employeeAdvance)
    {
        $employeeAdvance->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Advance) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeAdvance = EmployeeAdvance::onlyTrashed()->findOrFail($id);
        $employeeAdvance->restore();

        return response()->json($employeeAdvance);
    }

    /**
     * حذف نهائي للسجل من (Employee Advance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeAdvance::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Employee Advance).
     */
    public function nextCode(Request $request)
    {
        $last = EmployeeAdvance::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/ADV-(\d+)/', $last->advance_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'ADV-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Advance).
     */
    public function schema()
    {
        return ValidationRules::for('employee_advance', 'store');
    }
}
