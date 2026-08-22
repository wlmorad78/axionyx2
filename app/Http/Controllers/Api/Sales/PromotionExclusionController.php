<?php
/**
 * =====================================================================
 * متحكم (Controller): PromotionExclusionController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Promotion Exclusion
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Promotion Exclusion" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PromotionExclusion};
use App\Support\ValidationRules;

class PromotionExclusionController extends Controller
{
    /**
     * عرض قائمة سجلات (Promotion Exclusion) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PromotionExclusion::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Promotion Exclusion) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('promotion_exclusion', 'create'));
        $promotionExclusion = PromotionExclusion::create($data);
        return response()->json($promotionExclusion, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Promotion Exclusion) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return PromotionExclusion::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Promotion Exclusion) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $promotionExclusion = PromotionExclusion::findOrFail($id);
        $data = $request->validate(ValidationRules::for('promotion_exclusion', 'update', $promotionExclusion));
        $promotionExclusion->update($data);
        return $promotionExclusion;
    }

    /**
     * حذف سجل من (Promotion Exclusion) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $promotionExclusion = PromotionExclusion::findOrFail($id);
        $promotionExclusion->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Promotion Exclusion) وإعادته للعمل.
     */
    public function restore($id)
    {
        $promotionExclusion = PromotionExclusion::withTrashed()->findOrFail($id);
        $promotionExclusion->restore();
        return $promotionExclusion;
    }

    /**
     * حذف نهائي للسجل من (Promotion Exclusion) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $promotionExclusion = PromotionExclusion::withTrashed()->findOrFail($id);
        $promotionExclusion->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
