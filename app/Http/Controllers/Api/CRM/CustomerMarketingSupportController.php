<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerMarketingSupportController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Marketing Support
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Marketing Support" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerMarketingSupport;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerMarketingSupportController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Marketing Support) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerMarketingSupport::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('support_value', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Marketing Support) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_marketing_support', 'store'));
        return response()->json(CustomerMarketingSupport::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Marketing Support) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerMarketingSupport $customerMarketingSupport)
    {
        return $customerMarketingSupport->load(['customerAgreement', 'marketingSupportType']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Marketing Support) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerMarketingSupport $customerMarketingSupport)
    {
        $data = $request->validate(ValidationRules::for('customer_marketing_support', 'update', $customerMarketingSupport));
        $customerMarketingSupport->update($data);
        return response()->json($customerMarketingSupport);
    }

    /**
     * حذف سجل من (Customer Marketing Support) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerMarketingSupport $customerMarketingSupport)
    {
        $customerMarketingSupport->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Marketing Support) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerMarketingSupport::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Marketing Support) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerMarketingSupport::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Marketing Support).
     */
    public function schema()
    {
        return ValidationRules::for('customer_marketing_support', 'store');
    }
}
