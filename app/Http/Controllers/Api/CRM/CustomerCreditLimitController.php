<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerCreditLimitController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Credit Limit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Credit Limit" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerCreditLimit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerCreditLimitController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Credit Limit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerCreditLimit::with($with);

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Credit Limit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_credit_limit', 'store'));
        return response()->json(CustomerCreditLimit::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Credit Limit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerCreditLimit $customerCreditLimit)
    {
        return $customerCreditLimit->load(['customer']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Credit Limit) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerCreditLimit $customerCreditLimit)
    {
        $data = $request->validate(ValidationRules::for('customer_credit_limit', 'update', $customerCreditLimit));
        $customerCreditLimit->update($data);
        return response()->json($customerCreditLimit);
    }

    /**
     * حذف سجل من (Customer Credit Limit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerCreditLimit $customerCreditLimit)
    {
        $customerCreditLimit->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Credit Limit) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerCreditLimit::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Credit Limit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerCreditLimit::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Credit Limit).
     */
    public function schema()
    {
        return ValidationRules::for('customer_credit_limit', 'store');
    }
}
