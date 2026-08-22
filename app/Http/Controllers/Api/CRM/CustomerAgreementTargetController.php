<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerAgreementTargetController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Agreement Target
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Agreement Target" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementTarget;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementTargetController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Agreement Target) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementTarget::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('achievement_percent', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Agreement Target) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_target', 'store'));
        return response()->json(CustomerAgreementTarget::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Agreement Target) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerAgreementTarget $customerAgreementTarget)
    {
        return $customerAgreementTarget->load(['customerAgreement']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Agreement Target) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerAgreementTarget $customerAgreementTarget)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_target', 'update', $customerAgreementTarget));
        $customerAgreementTarget->update($data);
        return response()->json($customerAgreementTarget);
    }

    /**
     * حذف سجل من (Customer Agreement Target) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerAgreementTarget $customerAgreementTarget)
    {
        $customerAgreementTarget->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Agreement Target) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerAgreementTarget::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Agreement Target) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerAgreementTarget::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Agreement Target).
     */
    public function schema()
    {
        return ValidationRules::for('customer_agreement_target', 'store');
    }
}
