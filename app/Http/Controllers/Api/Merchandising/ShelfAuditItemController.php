<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfAuditItem;
use Illuminate\Http\Request;

class ShelfAuditItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ShelfAuditItem::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('shelf_audit_id')) {
            $query->where('shelf_audit_id', $request->shelf_audit_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'item_id' => 'required',
            'facings_count' => 'nullable|integer',
            'display_qty' => 'nullable|integer',
            'shelf_share_percent' => 'nullable|numeric',
        ]);

        $item = ShelfAuditItem::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = ShelfAuditItem::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = ShelfAuditItem::findOrFail($id);

        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'item_id' => 'required',
            'facings_count' => 'nullable|integer',
            'display_qty' => 'nullable|integer',
            'shelf_share_percent' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = ShelfAuditItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = ShelfAuditItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item);
    }

    public function forceDelete($id)
    {
        $item = ShelfAuditItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
