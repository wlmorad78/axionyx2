<?php
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PriceApprovalStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PriceApprovalStepController extends Controller {
    public function index(Request $request) {
        $query = PriceApprovalStep::with(['role', 'user']);
        if ($request->filled('price_approval_request_id')) $query->where('price_approval_request_id', $request->price_approval_request_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderBy('step_no')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('price_approval_step', 'create'));
        return response()->json(PriceApprovalStep::create($data), 201);
    }
    public function show($id) { return PriceApprovalStep::with(['role', 'user'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PriceApprovalStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('price_approval_step', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PriceApprovalStep::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
