<?php
/**
 * =====================================================================
 * متحكم (Controller): PricingRuleController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Pricing Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Pricing Rule" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PricingRule};
use App\Support\ValidationRules;

class PricingRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Pricing Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PricingRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('rule_code', 'like', "%{$s}%")
                  ->orWhere('rule_name', 'like', "%{$s}%")
                  ->orWhere('rule_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Pricing Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('pricing_rule', 'create'));
        $pricingRule = PricingRule::create($data);
        return response()->json($pricingRule, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Pricing Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return PricingRule::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Pricing Rule) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $pricingRule = PricingRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_rule', 'update', $pricingRule));
        $pricingRule->update($data);
        return $pricingRule;
    }

    /**
     * حذف سجل من (Pricing Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $pricingRule = PricingRule::findOrFail($id);
        $pricingRule->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Pricing Rule) وإعادته للعمل.
     */
    public function restore($id)
    {
        $pricingRule = PricingRule::withTrashed()->findOrFail($id);
        $pricingRule->restore();
        return $pricingRule;
    }

    /**
     * حذف نهائي للسجل من (Pricing Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $pricingRule = PricingRule::withTrashed()->findOrFail($id);
        $pricingRule->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
