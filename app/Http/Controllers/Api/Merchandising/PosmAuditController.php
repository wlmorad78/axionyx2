<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\PosmAudit;
use Illuminate\Http\Request;

class PosmAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = PosmAudit::with('marketingMaterial');

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('condition_status')) {
            $query->where('condition_status', $request->condition_status);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_material_id' => 'required',
            'is_available' => 'boolean',
            'condition_status' => 'required|in:GOOD,DAMAGED,MISSING',
        ]);

        $item = PosmAudit::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = PosmAudit::with('marketingMaterial')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = PosmAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'marketing_material_id' => 'required',
            'is_available' => 'boolean',
            'condition_status' => 'required|in:GOOD,DAMAGED,MISSING',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = PosmAudit::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = PosmAudit::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDelete($id)
    {
        $item = PosmAudit::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
