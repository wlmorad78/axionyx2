<?php
/**
 * =====================================================================
 * متحكم (Controller): PayrollRunController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Payroll Run
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Payroll Run" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    /**
     * عرض قائمة سجلات (Payroll Run) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PayrollRun::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->payroll_period_id) {
            $query->where('payroll_period_id', $request->payroll_period_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Payroll Run) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payroll_run', 'store'));

        return response()->json(PayrollRun::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Payroll Run) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PayrollRun $payrollRun)
    {
        return $payrollRun->load(['company', 'payrollPeriod', 'creator', 'details']);
    }

    /**
     * تحديث بيانات سجل موجود من (Payroll Run) بناءً على المعرّف.
     */
    public function update(Request $request, PayrollRun $payrollRun)
    {
        $data = $request->validate(ValidationRules::for('payroll_run', 'update', $payrollRun));

        $payrollRun->update($data);

        return response()->json($payrollRun);
    }

    /**
     * حذف سجل من (Payroll Run) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PayrollRun $payrollRun)
    {
        $payrollRun->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Payroll Run) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $payrollRun = PayrollRun::onlyTrashed()->findOrFail($id);

        $payrollRun->restore();

        return response()->json($payrollRun);
    }

    /**
     * حذف نهائي للسجل من (Payroll Run) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PayrollRun::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Payroll Run).
     */
    public function schema()
    {
        return ValidationRules::for('payroll_run', 'store');
    }
}
