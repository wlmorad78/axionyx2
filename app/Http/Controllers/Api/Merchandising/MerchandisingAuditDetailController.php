<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingAuditDetail;
use Illuminate\Http\Request;

class MerchandisingAuditDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingAuditDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        $details = $query->paginate($request->get('per_page', 15));

        return response()->json($details);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'merchandising_standard_item_id' => 'required',
            'score' => 'nullable|numeric',
            'remarks' => 'nullable',
        ]);

        $detail = MerchandisingAuditDetail::create($validated);

        return response()->json($detail, 201);
    }

    public function show($id)
    {
        $detail = MerchandisingAuditDetail::findOrFail($id);

        return response()->json($detail);
    }

    public function update(Request $request, $id)
    {
        $detail = MerchandisingAuditDetail::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'merchandising_standard_item_id' => 'required',
            'score' => 'nullable|numeric',
            'remarks' => 'nullable',
        ]);

        $detail->update($validated);

        return response()->json($detail);
    }

    public function destroy($id)
    {
        $detail = MerchandisingAuditDetail::findOrFail($id);
        $detail->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $detail = MerchandisingAuditDetail::onlyTrashed()->findOrFail($id);
        $detail->restore();

        return response()->json($detail);
    }

    public function forceDelete($id)
    {
        $detail = MerchandisingAuditDetail::onlyTrashed()->findOrFail($id);
        $detail->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
