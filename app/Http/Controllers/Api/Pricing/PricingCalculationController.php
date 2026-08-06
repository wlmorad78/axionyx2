<?php
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingCalculation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingCalculationController extends Controller {
    public function index(Request $request) {
        $query = PricingCalculation::with(['customer', 'item']);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('item_id')) $query->where('item_id', $request->item_id);
        if ($request->filled('reference_type')) $query->where('reference_type', $request->reference_type);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_calculation', 'create'));
        return response()->json(PricingCalculation::create($data), 201);
    }
    public function show($id) { return PricingCalculation::with(['customer', 'item', 'unit', 'pricingRule', 'details'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PricingCalculation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_calculation', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PricingCalculation::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
