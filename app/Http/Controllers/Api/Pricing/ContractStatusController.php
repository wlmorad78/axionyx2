<?php
/**
 * =====================================================================
 * متحكم (Controller): ContractStatusController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Contract Status
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Contract Status" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\ContractStatus;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ContractStatusController extends Controller
{
    /**
     * عرض قائمة سجلات (Contract Status) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ContractStatus::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Contract Status) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('contract_status', 'store'));

        return response()->json(ContractStatus::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Contract Status) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ContractStatus $contractStatus)
    {
        return $contractStatus;
    }

    /**
     * تحديث بيانات سجل موجود من (Contract Status) بناءً على المعرّف.
     */
    public function update(Request $request, ContractStatus $contractStatus)
    {
        $data = $request->validate(ValidationRules::for('contract_status', 'update', $contractStatus));

        $contractStatus->update($data);

        return response()->json($contractStatus);
    }

    /**
     * حذف سجل من (Contract Status) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ContractStatus $contractStatus)
    {
        if ($contractStatus->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $contractStatus->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Contract Status) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $contractStatus = ContractStatus::onlyTrashed()->findOrFail($id);
        $contractStatus->restore();

        return response()->json($contractStatus);
    }

    /**
     * حذف نهائي للسجل من (Contract Status) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ContractStatus::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Contract Status).
     */
    public function schema()
    {
        return ValidationRules::for('contract_status', 'store');
    }
}
