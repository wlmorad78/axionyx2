<?php
/**
 * =====================================================================
 * متحكم (Controller): ContractTypeController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Contract Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Contract Type" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\ContractType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ContractTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Contract Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ContractType::with($with);

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
     * إنشاء سجل جديد لـ (Contract Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('contract_type', 'store'));

        return response()->json(ContractType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Contract Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ContractType $contractType)
    {
        return $contractType;
    }

    /**
     * تحديث بيانات سجل موجود من (Contract Type) بناءً على المعرّف.
     */
    public function update(Request $request, ContractType $contractType)
    {
        $data = $request->validate(ValidationRules::for('contract_type', 'update', $contractType));

        $contractType->update($data);

        return response()->json($contractType);
    }

    /**
     * حذف سجل من (Contract Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ContractType $contractType)
    {
        if ($contractType->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $contractType->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Contract Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $contractType = ContractType::onlyTrashed()->findOrFail($id);
        $contractType->restore();

        return response()->json($contractType);
    }

    /**
     * حذف نهائي للسجل من (Contract Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ContractType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Contract Type).
     */
    public function schema()
    {
        return ValidationRules::for('contract_type', 'store');
    }
}
