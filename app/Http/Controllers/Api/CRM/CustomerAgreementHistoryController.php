<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerAgreementHistoryController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Agreement History
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Agreement History" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementHistory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementHistoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Agreement History) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementHistory::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('action_type', 'like', "%$s%");
            });
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Agreement History) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_history', 'store'));
        return response()->json(CustomerAgreementHistory::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Agreement History) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerAgreementHistory $customerAgreementHistory)
    {
        return $customerAgreementHistory->load(['customerAgreement', 'actionBy']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Agreement History) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerAgreementHistory $customerAgreementHistory)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_history', 'update', $customerAgreementHistory));
        $customerAgreementHistory->update($data);
        return response()->json($customerAgreementHistory);
    }

    /**
     * حذف سجل من (Customer Agreement History) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerAgreementHistory $customerAgreementHistory)
    {
        $customerAgreementHistory->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Agreement History) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerAgreementHistory::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Agreement History) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerAgreementHistory::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Agreement History).
     */
    public function schema()
    {
        return ValidationRules::for('customer_agreement_history', 'store');
    }
}
