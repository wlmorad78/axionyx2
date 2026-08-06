<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorShelfItem;
use Illuminate\Http\Request;

class CompetitorShelfItemController extends Controller
{
    public function index(Request $request)
    {
        $query = CompetitorShelfItem::with('competitorProduct');

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
            'competitor_product_id' => 'required',
            'facings_count' => 'integer',
            'shelf_share_percent' => 'numeric',
        ]);

        $item = CompetitorShelfItem::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = CompetitorShelfItem::with('competitorProduct')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = CompetitorShelfItem::findOrFail($id);

        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'competitor_product_id' => 'required',
            'facings_count' => 'integer',
            'shelf_share_percent' => 'numeric',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = CompetitorShelfItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = CompetitorShelfItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDelete($id)
    {
        $item = CompetitorShelfItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
