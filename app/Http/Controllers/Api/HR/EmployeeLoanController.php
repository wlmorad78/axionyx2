<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeLoanController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Loan
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Loan" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLoan;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeLoanController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Loan) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeLoan::with($with);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Loan) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_loan', 'store'));

        return response()->json(EmployeeLoan::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Loan) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeLoan $employeeLoan)
    {
        return $employeeLoan->load(['employee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Loan) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeLoan $employeeLoan)
    {
        $data = $request->validate(ValidationRules::for('employee_loan', 'update', $employeeLoan));

        $employeeLoan->update($data);

        return response()->json($employeeLoan);
    }

    /**
     * حذف سجل من (Employee Loan) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeLoan $employeeLoan)
    {
        $employeeLoan->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Loan) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeLoan = EmployeeLoan::onlyTrashed()->findOrFail($id);
        $employeeLoan->restore();

        return response()->json($employeeLoan);
    }

    /**
     * حذف نهائي للسجل من (Employee Loan) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeLoan::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Employee Loan).
     */
    public function nextCode(Request $request)
    {
        $last = EmployeeLoan::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/LN-(\d+)/', $last->loan_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'LN-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Loan).
     */
    public function schema()
    {
        return ValidationRules::for('employee_loan', 'store');
    }
}
