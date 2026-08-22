<?php
/**
 * =====================================================================
 * متحكم (Controller): PriceLevelController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Price Level
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Price Level" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PriceLevel};
use App\Support\ValidationRules;

class PriceLevelController extends Controller
{
    /**
     * عرض قائمة سجلات (Price Level) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PriceLevel::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('level_code', 'like', "%{$s}%")
                  ->orWhere('level_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Price Level) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('price_level', 'create'));
        $priceLevel = PriceLevel::create($data);
        return response()->json($priceLevel, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Price Level) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return PriceLevel::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Price Level) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $priceLevel = PriceLevel::findOrFail($id);
        $data = $request->validate(ValidationRules::for('price_level', 'update', $priceLevel));
        $priceLevel->update($data);
        return $priceLevel;
    }

    /**
     * حذف سجل من (Price Level) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $priceLevel = PriceLevel::findOrFail($id);
        $priceLevel->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Price Level) وإعادته للعمل.
     */
    public function restore($id)
    {
        $priceLevel = PriceLevel::withTrashed()->findOrFail($id);
        $priceLevel->restore();
        return $priceLevel;
    }

    /**
     * حذف نهائي للسجل من (Price Level) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $priceLevel = PriceLevel::withTrashed()->findOrFail($id);
        $priceLevel->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
