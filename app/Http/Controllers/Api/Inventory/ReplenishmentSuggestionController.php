<?php
/**
 * =====================================================================
 * متحكم (Controller): ReplenishmentSuggestionController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Replenishment Suggestion
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Replenishment Suggestion" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ReplenishmentSuggestion};
use App\Support\ValidationRules;

class ReplenishmentSuggestionController extends Controller
{
    /**
     * عرض قائمة سجلات (Replenishment Suggestion) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ReplenishmentSuggestion::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%")
                  ->orWhere('suggested_qty', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Replenishment Suggestion) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('replenishment_suggestion', 'create'));
        $replenishmentSuggestion = ReplenishmentSuggestion::create($data);
        return response()->json($replenishmentSuggestion, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Replenishment Suggestion) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ReplenishmentSuggestion::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Replenishment Suggestion) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::findOrFail($id);
        $data = $request->validate(ValidationRules::for('replenishment_suggestion', 'update', $replenishmentSuggestion));
        $replenishmentSuggestion->update($data);
        return $replenishmentSuggestion;
    }

    /**
     * حذف سجل من (Replenishment Suggestion) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::findOrFail($id);
        $replenishmentSuggestion->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Replenishment Suggestion) وإعادته للعمل.
     */
    public function restore($id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::withTrashed()->findOrFail($id);
        $replenishmentSuggestion->restore();
        return $replenishmentSuggestion;
    }

    /**
     * حذف نهائي للسجل من (Replenishment Suggestion) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::withTrashed()->findOrFail($id);
        $replenishmentSuggestion->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
