<?php
/**
 * =====================================================================
 * متحكم (Controller): PricingRuleConditionController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Pricing Rule Condition
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Pricing Rule Condition" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingRuleCondition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingRuleConditionController extends Controller {
    public function index(Request $request) {
        $query = PricingRuleCondition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('pricing_rule_id')) $query->where('pricing_rule_id', $request->pricing_rule_id);
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('condition_type', 'like', "%{$s}%")->orWhere('condition_value', 'like', "%{$s}%");
            });
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_rule_condition', 'create'));
        return response()->json(PricingRuleCondition::create($data), 201);
    }
    public function show($id) { return PricingRuleCondition::findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PricingRuleCondition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_rule_condition', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PricingRuleCondition::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
