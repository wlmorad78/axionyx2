<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerAddressController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Address
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Address" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Address) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAddress::with($with);

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
     * إنشاء سجل جديد لـ (Customer Address) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_address', 'store'));
        return response()->json(CustomerAddress::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Address) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerAddress $customerAddress)
    {
        return $customerAddress->load(['customer', 'country', 'governorate', 'city', 'area']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Address) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerAddress $customerAddress)
    {
        $data = $request->validate(ValidationRules::for('customer_address', 'update', $customerAddress));
        $customerAddress->update($data);
        return response()->json($customerAddress);
    }

    /**
     * حذف سجل من (Customer Address) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerAddress $customerAddress)
    {
        $customerAddress->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Address) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerAddress::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Address) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerAddress::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Address).
     */
    public function schema()
    {
        return ValidationRules::for('customer_address', 'store');
    }
}
