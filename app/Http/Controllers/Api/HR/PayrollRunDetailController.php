<?php
/**
 * =====================================================================
 * متحكم (Controller): PayrollRunDetailController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Payroll Run Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Payroll Run Detail" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollRunDetail;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PayrollRunDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Payroll Run Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PayrollRunDetail::with($with);

        if ($request->payroll_run_id) {
            $query->where('payroll_run_id', $request->payroll_run_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Payroll Run Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payroll_run_detail', 'store'));

        return response()->json(PayrollRunDetail::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Payroll Run Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PayrollRunDetail $payrollRunDetail)
    {
        return $payrollRunDetail->load(['payrollRun', 'employee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Payroll Run Detail) بناءً على المعرّف.
     */
    public function update(Request $request, PayrollRunDetail $payrollRunDetail)
    {
        $data = $request->validate(ValidationRules::for('payroll_run_detail', 'update', $payrollRunDetail));

        $payrollRunDetail->update($data);

        return response()->json($payrollRunDetail);
    }

    /**
     * حذف سجل من (Payroll Run Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PayrollRunDetail $payrollRunDetail)
    {
        $payrollRunDetail->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Payroll Run Detail) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $payrollRunDetail = PayrollRunDetail::onlyTrashed()->findOrFail($id);

        $payrollRunDetail->restore();

        return response()->json($payrollRunDetail);
    }

    /**
     * حذف نهائي للسجل من (Payroll Run Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PayrollRunDetail::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Payroll Run Detail).
     */
    public function schema()
    {
        return ValidationRules::for('payroll_run_detail', 'store');
    }
}
