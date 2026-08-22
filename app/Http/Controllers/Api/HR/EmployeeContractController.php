<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeContractController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee Contract
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee Contract" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeContract;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeContractController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee Contract) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeContract::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->contract_type_id) {
            $query->where('contract_type_id', $request->contract_type_id);
        }

        if ($request->contract_status_id) {
            $query->where('contract_status_id', $request->contract_status_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('contract_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee Contract) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_contract', 'store'));

        return response()->json(EmployeeContract::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee Contract) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(EmployeeContract $employeeContract)
    {
        return $employeeContract->load(['company', 'employee', 'contractType', 'contractStatus', 'amendments']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee Contract) بناءً على المعرّف.
     */
    public function update(Request $request, EmployeeContract $employeeContract)
    {
        $data = $request->validate(ValidationRules::for('employee_contract', 'update', $employeeContract));

        $employeeContract->update($data);

        return response()->json($employeeContract);
    }

    /**
     * حذف سجل من (Employee Contract) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(EmployeeContract $employeeContract)
    {
        $employeeContract->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Employee Contract) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $employeeContract = EmployeeContract::onlyTrashed()->findOrFail($id);
        $employeeContract->restore();

        return response()->json($employeeContract);
    }

    /**
     * حذف نهائي للسجل من (Employee Contract) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        EmployeeContract::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Employee Contract).
     */
    public function nextCode(Request $request)
    {
        $last = EmployeeContract::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/CTR-(\d+)/', $last->contract_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'CTR-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Employee Contract).
     */
    public function schema()
    {
        return ValidationRules::for('employee_contract', 'store');
    }
}
