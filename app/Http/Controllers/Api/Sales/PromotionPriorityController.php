<?php
/**
 * =====================================================================
 * متحكم (Controller): PromotionPriorityController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Promotion Priority
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Promotion Priority" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PromotionPriority};
use App\Support\ValidationRules;

class PromotionPriorityController extends Controller
{
    /**
     * عرض قائمة سجلات (Promotion Priority) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PromotionPriority::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('priority', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Promotion Priority) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('promotion_priority', 'create'));
        $promotionPriority = PromotionPriority::create($data);
        return response()->json($promotionPriority, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Promotion Priority) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return PromotionPriority::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Promotion Priority) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $promotionPriority = PromotionPriority::findOrFail($id);
        $data = $request->validate(ValidationRules::for('promotion_priority', 'update', $promotionPriority));
        $promotionPriority->update($data);
        return $promotionPriority;
    }

    /**
     * حذف سجل من (Promotion Priority) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $promotionPriority = PromotionPriority::findOrFail($id);
        $promotionPriority->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Promotion Priority) وإعادته للعمل.
     */
    public function restore($id)
    {
        $promotionPriority = PromotionPriority::withTrashed()->findOrFail($id);
        $promotionPriority->restore();
        return $promotionPriority;
    }

    /**
     * حذف نهائي للسجل من (Promotion Priority) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $promotionPriority = PromotionPriority::withTrashed()->findOrFail($id);
        $promotionPriority->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
