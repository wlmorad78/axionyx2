<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\RefrigeratorAudit;
use Illuminate\Http\Request;

class RefrigeratorAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = RefrigeratorAudit::with('marketingAsset');

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('working_status')) {
            $query->where('working_status', $request->working_status);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_asset_id' => 'required',
            'temperature' => 'numeric',
            'cleanliness_score' => 'numeric',
            'working_status' => 'required|in:WORKING,NEEDS_MAINTENANCE,OUT_OF_SERVICE',
            'notes' => 'nullable|string',
        ]);

        $item = RefrigeratorAudit::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = RefrigeratorAudit::with('marketingAsset')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = RefrigeratorAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_asset_id' => 'required',
            'temperature' => 'numeric',
            'cleanliness_score' => 'numeric',
            'working_status' => 'required|in:WORKING,NEEDS_MAINTENANCE,OUT_OF_SERVICE',
            'notes' => 'nullable|string',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = RefrigeratorAudit::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = RefrigeratorAudit::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDelete($id)
    {
        $item = RefrigeratorAudit::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
