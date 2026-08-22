<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerAgreementPaymentController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Agreement Payment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Agreement Payment" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementPayment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementPaymentController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Agreement Payment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementPayment::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('payment_type', 'like', "%$s%")
                    ->orWhere('amount', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Agreement Payment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_payment', 'store'));
        return response()->json(CustomerAgreementPayment::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Agreement Payment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerAgreementPayment $customerAgreementPayment)
    {
        return $customerAgreementPayment->load(['customerAgreement']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Agreement Payment) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerAgreementPayment $customerAgreementPayment)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_payment', 'update', $customerAgreementPayment));
        $customerAgreementPayment->update($data);
        return response()->json($customerAgreementPayment);
    }

    /**
     * حذف سجل من (Customer Agreement Payment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerAgreementPayment $customerAgreementPayment)
    {
        $customerAgreementPayment->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Agreement Payment) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerAgreementPayment::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Agreement Payment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerAgreementPayment::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Agreement Payment).
     */
    public function schema()
    {
        return ValidationRules::for('customer_agreement_payment', 'store');
    }
}
