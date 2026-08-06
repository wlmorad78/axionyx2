<?php
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingRuleItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingRuleItemController extends Controller {
    public function index(Request $request) {
        $query = PricingRuleItem::with(['item', 'unit']);
        if ($request->filled('pricing_rule_id')) $query->where('pricing_rule_id', $request->pricing_rule_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_rule_item', 'create'));
        return response()->json(PricingRuleItem::create($data), 201);
    }
    public function show($id) { return PricingRuleItem::with(['item', 'unit', 'quantityBreaks'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PricingRuleItem::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_rule_item', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PricingRuleItem::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
