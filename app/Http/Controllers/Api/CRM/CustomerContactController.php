<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerContactController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Contact
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Contact" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerContact;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerContactController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Contact) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerContact::with($with);

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Contact) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_contact', 'store'));
        return response()->json(CustomerContact::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Contact) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerContact $customerContact)
    {
        return $customerContact->load(['customer']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Contact) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerContact $customerContact)
    {
        $data = $request->validate(ValidationRules::for('customer_contact', 'update', $customerContact));
        $customerContact->update($data);
        return response()->json($customerContact);
    }

    /**
     * حذف سجل من (Customer Contact) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerContact $customerContact)
    {
        $customerContact->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Contact) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerContact::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Contact) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerContact::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Contact).
     */
    public function schema()
    {
        return ValidationRules::for('customer_contact', 'store');
    }
}
