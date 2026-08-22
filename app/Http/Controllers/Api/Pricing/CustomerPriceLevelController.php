<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerPriceLevelController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Customer Price Level
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Price Level" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CustomerPriceLevel};
use App\Support\ValidationRules;

class CustomerPriceLevelController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Price Level) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = CustomerPriceLevel::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Price Level) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_price_level', 'create'));
        $customerPriceLevel = CustomerPriceLevel::create($data);
        return response()->json($customerPriceLevel, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Price Level) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return CustomerPriceLevel::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Price Level) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $customerPriceLevel = CustomerPriceLevel::findOrFail($id);
        $data = $request->validate(ValidationRules::for('customer_price_level', 'update', $customerPriceLevel));
        $customerPriceLevel->update($data);
        return $customerPriceLevel;
    }

    /**
     * حذف سجل من (Customer Price Level) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $customerPriceLevel = CustomerPriceLevel::findOrFail($id);
        $customerPriceLevel->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Price Level) وإعادته للعمل.
     */
    public function restore($id)
    {
        $customerPriceLevel = CustomerPriceLevel::withTrashed()->findOrFail($id);
        $customerPriceLevel->restore();
        return $customerPriceLevel;
    }

    /**
     * حذف نهائي للسجل من (Customer Price Level) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $customerPriceLevel = CustomerPriceLevel::withTrashed()->findOrFail($id);
        $customerPriceLevel->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
