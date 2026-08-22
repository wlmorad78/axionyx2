<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerAgreementTypeController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Agreement Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Agreement Type" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Agreement Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%$s%")
                    ->orWhere('name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Agreement Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_type', 'store'));
        return response()->json(CustomerAgreementType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Agreement Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerAgreementType $customerAgreementType)
    {
        return $customerAgreementType->load(['agreements']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Agreement Type) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerAgreementType $customerAgreementType)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_type', 'update', $customerAgreementType));
        $customerAgreementType->update($data);
        return response()->json($customerAgreementType);
    }

    /**
     * حذف سجل من (Customer Agreement Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerAgreementType $customerAgreementType)
    {
        $customerAgreementType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Agreement Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerAgreementType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Agreement Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerAgreementType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Agreement Type).
     */
    public function schema()
    {
        return ValidationRules::for('customer_agreement_type', 'store');
    }
}
