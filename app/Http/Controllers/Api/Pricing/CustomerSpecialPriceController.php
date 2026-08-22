<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerSpecialPriceController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Customer Special Price
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Special Price" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CustomerSpecialPrice};
use App\Support\ValidationRules;

class CustomerSpecialPriceController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Special Price) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = CustomerSpecialPrice::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('price', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Special Price) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_special_price', 'create'));
        $customerSpecialPrice = CustomerSpecialPrice::create($data);
        return response()->json($customerSpecialPrice, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Special Price) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return CustomerSpecialPrice::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Special Price) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('customer_special_price', 'update', $customerSpecialPrice));
        $customerSpecialPrice->update($data);
        return $customerSpecialPrice;
    }

    /**
     * حذف سجل من (Customer Special Price) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::findOrFail($id);
        $customerSpecialPrice->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Special Price) وإعادته للعمل.
     */
    public function restore($id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::withTrashed()->findOrFail($id);
        $customerSpecialPrice->restore();
        return $customerSpecialPrice;
    }

    /**
     * حذف نهائي للسجل من (Customer Special Price) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $customerSpecialPrice = CustomerSpecialPrice::withTrashed()->findOrFail($id);
        $customerSpecialPrice->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
