<?php
/**
 * =====================================================================
 * متحكم (Controller): CompanySubscriptionLimitController
 * الوحدة (Module): بيانات الشركة (Company)
 * المورد (Resource): Company Subscription Limit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Company Subscription Limit" ضمن وحدة "بيانات الشركة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanySubscriptionLimit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CompanySubscriptionLimitController extends Controller
{
    /**
     * عرض قائمة سجلات (Company Subscription Limit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompanySubscriptionLimit::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Company Subscription Limit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('company_subscription_limit', 'store'));
        $limit = CompanySubscriptionLimit::create($data);

        return response()->json($limit, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Company Subscription Limit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompanySubscriptionLimit $companySubscriptionLimit)
    {
        return $companySubscriptionLimit;
    }

    /**
     * تحديث بيانات سجل موجود من (Company Subscription Limit) بناءً على المعرّف.
     */
    public function update(Request $request, CompanySubscriptionLimit $companySubscriptionLimit)
    {
        $data = $request->validate(ValidationRules::for('company_subscription_limit', 'update', $companySubscriptionLimit));
        $companySubscriptionLimit->update($data);

        return response()->json($companySubscriptionLimit);
    }

    /**
     * حذف سجل من (Company Subscription Limit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompanySubscriptionLimit $companySubscriptionLimit)
    {
        $companySubscriptionLimit->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Company Subscription Limit) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $limit = CompanySubscriptionLimit::onlyTrashed()->findOrFail($id);
        $limit->restore();

        return response()->json($limit);
    }

    /**
     * حذف نهائي للسجل من (Company Subscription Limit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $limit = CompanySubscriptionLimit::onlyTrashed()->findOrFail($id);
        $limit->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Company Subscription Limit).
     */
    public function schema()
    {
        return ValidationRules::for('company_subscription_limit', 'store');
    }
}
