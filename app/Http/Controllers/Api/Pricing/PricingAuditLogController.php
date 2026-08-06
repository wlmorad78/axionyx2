<?php
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingAuditLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingAuditLogController extends Controller {
    public function index(Request $request) {
        $query = PricingAuditLog::with(['customer', 'item']);
        if ($request->filled('reference_type')) $query->where('reference_type', $request->reference_type);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('item_id')) $query->where('item_id', $request->item_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_audit_log', 'create'));
        return response()->json(PricingAuditLog::create($data), 201);
    }
    public function show($id) { return PricingAuditLog::with(['customer', 'item'])->findOrFail($id); }
}
