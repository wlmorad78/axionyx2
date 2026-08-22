<?php
/**
 * =====================================================================
 * متحكم (Controller): ReplenishmentRuleController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Replenishment Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Replenishment Rule" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ReplenishmentRule};
use App\Support\ValidationRules;

class ReplenishmentRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Replenishment Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ReplenishmentRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('lead_time_days', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Replenishment Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('replenishment_rule', 'create'));
        $replenishmentRule = ReplenishmentRule::create($data);
        return response()->json($replenishmentRule, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Replenishment Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ReplenishmentRule::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Replenishment Rule) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $replenishmentRule = ReplenishmentRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('replenishment_rule', 'update', $replenishmentRule));
        $replenishmentRule->update($data);
        return $replenishmentRule;
    }

    /**
     * حذف سجل من (Replenishment Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $replenishmentRule = ReplenishmentRule::findOrFail($id);
        $replenishmentRule->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Replenishment Rule) وإعادته للعمل.
     */
    public function restore($id)
    {
        $replenishmentRule = ReplenishmentRule::withTrashed()->findOrFail($id);
        $replenishmentRule->restore();
        return $replenishmentRule;
    }

    /**
     * حذف نهائي للسجل من (Replenishment Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $replenishmentRule = ReplenishmentRule::withTrashed()->findOrFail($id);
        $replenishmentRule->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
