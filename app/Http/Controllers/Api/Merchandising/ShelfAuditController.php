<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfAudit;
use Illuminate\Http\Request;

class ShelfAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = ShelfAudit::with(['location', 'items', 'competitorItems']);

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        $shelfAudits = $query->paginate($request->get('per_page', 15));

        return response()->json($shelfAudits);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'display_location_id' => 'required',
            'shelf_length' => 'nullable|numeric',
            'shelf_width' => 'nullable|numeric',
            'shelf_height' => 'nullable|numeric',
        ]);

        $shelfAudit = ShelfAudit::create($validated);

        return response()->json($shelfAudit->load(['location', 'items', 'competitorItems']), 201);
    }

    public function show($id)
    {
        $shelfAudit = ShelfAudit::with(['location', 'items', 'competitorItems'])->findOrFail($id);

        return response()->json($shelfAudit);
    }

    public function update(Request $request, $id)
    {
        $shelfAudit = ShelfAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'display_location_id' => 'required',
            'shelf_length' => 'nullable|numeric',
            'shelf_width' => 'nullable|numeric',
            'shelf_height' => 'nullable|numeric',
        ]);

        $shelfAudit->update($validated);

        return response()->json($shelfAudit->load(['location', 'items', 'competitorItems']));
    }

    public function destroy($id)
    {
        $shelfAudit = ShelfAudit::findOrFail($id);
        $shelfAudit->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $shelfAudit = ShelfAudit::onlyTrashed()->findOrFail($id);
        $shelfAudit->restore();

        return response()->json($shelfAudit);
    }

    public function forceDelete($id)
    {
        $shelfAudit = ShelfAudit::onlyTrashed()->findOrFail($id);
        $shelfAudit->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
