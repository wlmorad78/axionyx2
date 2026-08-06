<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingStandardItem;
use Illuminate\Http\Request;

class MerchandisingStandardItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingStandardItem::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('merchandising_standard_id')) {
            $query->where('merchandising_standard_id', $request->merchandising_standard_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_standard_id' => 'required',
            'item_no' => 'required|integer',
            'item_name' => 'required',
            'score' => 'nullable|integer',
            'display_order' => 'nullable|integer',
        ]);

        $item = MerchandisingStandardItem::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = MerchandisingStandardItem::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = MerchandisingStandardItem::findOrFail($id);

        $validated = $request->validate([
            'merchandising_standard_id' => 'required',
            'item_no' => 'required|integer',
            'item_name' => 'required',
            'score' => 'nullable|integer',
            'display_order' => 'nullable|integer',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = MerchandisingStandardItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = MerchandisingStandardItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item);
    }

    public function forceDelete($id)
    {
        $item = MerchandisingStandardItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
