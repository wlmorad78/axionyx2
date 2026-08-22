<?php
/**
 * =====================================================================
 * متحكم (Controller): CompanySubscriptionController
 * الوحدة (Module): بيانات الشركة (Company)
 * المورد (Resource): Company Subscription
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Company Subscription" ضمن وحدة "بيانات الشركة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Settings\CompanySubscription;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CompanySubscriptionController extends Controller
{
    /**
     * عرض قائمة سجلات (Company Subscription) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompanySubscription::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Company Subscription) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('company_subscription', 'store'));
        $subscription = CompanySubscription::create($data);

        return response()->json($subscription, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Company Subscription) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompanySubscription $companySubscription)
    {
        return $companySubscription;
    }

    /**
     * تحديث بيانات سجل موجود من (Company Subscription) بناءً على المعرّف.
     */
    public function update(Request $request, CompanySubscription $companySubscription)
    {
        $data = $request->validate(ValidationRules::for('company_subscription', 'update', $companySubscription));
        $companySubscription->update($data);

        return response()->json($companySubscription);
    }

    /**
     * حذف سجل من (Company Subscription) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompanySubscription $companySubscription)
    {
        $companySubscription->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Company Subscription) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $subscription = CompanySubscription::onlyTrashed()->findOrFail($id);
        $subscription->restore();

        return response()->json($subscription);
    }

    /**
     * حذف نهائي للسجل من (Company Subscription) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $subscription = CompanySubscription::onlyTrashed()->findOrFail($id);
        $subscription->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Company Subscription).
     */
    public function schema()
    {
        return ValidationRules::for('company_subscription', 'store');
    }
}
