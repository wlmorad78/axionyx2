<?php
/**
 * =====================================================================
 * متحكم (Controller): PayrollPeriodController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Payroll Period
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Payroll Period" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PayrollPeriodController extends Controller
{
    /**
     * عرض قائمة سجلات (Payroll Period) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PayrollPeriod::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('period_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Payroll Period) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payroll_period', 'store'));

        return response()->json(PayrollPeriod::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Payroll Period) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PayrollPeriod $payrollPeriod)
    {
        return $payrollPeriod->load(['company', 'payrollRuns']);
    }

    /**
     * تحديث بيانات سجل موجود من (Payroll Period) بناءً على المعرّف.
     */
    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        $data = $request->validate(ValidationRules::for('payroll_period', 'update', $payrollPeriod));

        $payrollPeriod->update($data);

        return response()->json($payrollPeriod);
    }

    /**
     * حذف سجل من (Payroll Period) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PayrollPeriod $payrollPeriod)
    {
        $payrollPeriod->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Payroll Period) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $payrollPeriod = PayrollPeriod::onlyTrashed()->findOrFail($id);

        $payrollPeriod->restore();

        return response()->json($payrollPeriod);
    }

    /**
     * حذف نهائي للسجل من (Payroll Period) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PayrollPeriod::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Payroll Period).
     */
    public function schema()
    {
        return ValidationRules::for('payroll_period', 'store');
    }
}
