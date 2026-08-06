<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PricingRule};
use App\Support\ValidationRules;

class PricingRuleController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('pricing_rule', 'create'));
        $pricingRule = PricingRule::create($data);
        return response()->json($pricingRule, 201);
    }

    public function show($id)
    {
        return PricingRule::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $pricingRule = PricingRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_rule', 'update', $pricingRule));
        $pricingRule->update($data);
        return $pricingRule;
    }

    public function destroy($id)
    {
        $pricingRule = PricingRule::findOrFail($id);
        $pricingRule->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $pricingRule = PricingRule::withTrashed()->findOrFail($id);
        $pricingRule->restore();
        return $pricingRule;
    }

    public function forceDelete($id)
    {
        $pricingRule = PricingRule::withTrashed()->findOrFail($id);
        $pricingRule->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
