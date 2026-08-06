<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityAudit;
use Illuminate\Http\Request;

class AvailabilityAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AvailabilityAudit::with('item');

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'item_id' => 'required',
            'is_available' => 'boolean',
            'stock_qty' => 'integer',
        ]);

        $item = AvailabilityAudit::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = AvailabilityAudit::with('item')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = AvailabilityAudit::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'item_id' => 'required',
            'is_available' => 'boolean',
            'stock_qty' => 'integer',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = AvailabilityAudit::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = AvailabilityAudit::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDelete($id)
    {
        $item = AvailabilityAudit::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
