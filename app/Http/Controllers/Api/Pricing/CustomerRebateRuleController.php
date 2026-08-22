<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerRebateRuleController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Customer Rebate Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Rebate Rule" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\CustomerRebateRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerRebateRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Rebate Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerRebateRule::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rebate_percent', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Rebate Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_rebate_rule', 'store'));
        return response()->json(CustomerRebateRule::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Rebate Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerRebateRule $customerRebateRule)
    {
        return $customerRebateRule->load(['customerAgreement']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Rebate Rule) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerRebateRule $customerRebateRule)
    {
        $data = $request->validate(ValidationRules::for('customer_rebate_rule', 'update', $customerRebateRule));
        $customerRebateRule->update($data);
        return response()->json($customerRebateRule);
    }

    /**
     * حذف سجل من (Customer Rebate Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerRebateRule $customerRebateRule)
    {
        $customerRebateRule->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Rebate Rule) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerRebateRule::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Rebate Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerRebateRule::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Rebate Rule).
     */
    public function schema()
    {
        return ValidationRules::for('customer_rebate_rule', 'store');
    }
}
