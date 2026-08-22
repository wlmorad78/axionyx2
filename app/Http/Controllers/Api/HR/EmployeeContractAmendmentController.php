<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeContractAmendmentController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Contract Amendment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Contract Amendment" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeContractAmendment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeContractAmendmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Contract Amendment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeContractAmendment::with($with);

        if ($request->employee_contract_id) {
            $query->where('employee_contract_id', $request->employee_contract_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('amendment_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Contract Amendment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_contract_amendment', 'store'));

        return response()->json(EmployeeContractAmendment::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Contract Amendment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeContractAmendment $employeeContractAmendment)
    {
        return $employeeContractAmendment->load(['contract']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Contract Amendment) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeContractAmendment $employeeContractAmendment)
    {
        $data = $request->validate(ValidationRules::for('employee_contract_amendment', 'update', $employeeContractAmendment));

        $employeeContractAmendment->update($data);

        return response()->json($employeeContractAmendment);
    }

    /**
     * حذف سجل من (Employee Contract Amendment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeContractAmendment $employeeContractAmendment)
    {
        $employeeContractAmendment->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Contract Amendment) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeContractAmendment = EmployeeContractAmendment::onlyTrashed()->findOrFail($id);
        $employeeContractAmendment->restore();

        return response()->json($employeeContractAmendment);
    }

    /**
     * حذف نهائي للسجل من (Employee Contract Amendment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeContractAmendment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Employee Contract Amendment).
     */
    public function nextCode(Request $request)
    {
        $last = EmployeeContractAmendment::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/AMD-(\d+)/', $last->amendment_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'AMD-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Contract Amendment).
     */
    public function schema()
    {
        return ValidationRules::for('employee_contract_amendment', 'store');
    }
}
