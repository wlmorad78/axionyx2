<?php
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingMethod;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingMethodController extends Controller {
    public function index(Request $request) {
        $query = PricingMethod::query();
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('method_code', 'like', "%{$s}%")->orWhere('method_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('is_active')) $query->where('is_active', $request->boolean('is_active'));
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_method', 'create'));
        return response()->json(PricingMethod::create($data), 201);
    }
    public function show($id) { return PricingMethod::findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PricingMethod::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_method', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PricingMethod::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
    public function restore($id) { $m = PricingMethod::withTrashed()->findOrFail($id); $m->restore(); return $m; }
    public function forceDelete($id) { PricingMethod::withTrashed()->findOrFail($id)->forceDelete(); return response()->json(['message' => 'Permanently deleted']); }
}
