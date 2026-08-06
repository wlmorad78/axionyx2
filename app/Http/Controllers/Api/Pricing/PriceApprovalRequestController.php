<?php
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PriceApprovalRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PriceApprovalRequestController extends Controller {
    public function index(Request $request) {
        $query = PriceApprovalRequest::with(['customer', 'item', 'requestedBy']);
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('price_approval_request', 'create'));
        if (empty($data['request_no'])) $data['request_no'] = 'PAR-' . str_pad(PriceApprovalRequest::max('id') + 1, 5, '0', STR_PAD_LEFT);
        return response()->json(PriceApprovalRequest::create($data), 201);
    }
    public function show($id) { return PriceApprovalRequest::with(['customer', 'item', 'requestedBy', 'steps.role', 'steps.user'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PriceApprovalRequest::findOrFail($id);
        $data = $request->validate(ValidationRules::for('price_approval_request', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PriceApprovalRequest::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
    public function restore($id) { $m = PriceApprovalRequest::withTrashed()->findOrFail($id); $m->restore(); return $m; }
    public function forceDelete($id) { PriceApprovalRequest::withTrashed()->findOrFail($id)->forceDelete(); return response()->json(['message' => 'Permanently deleted']); }
}
