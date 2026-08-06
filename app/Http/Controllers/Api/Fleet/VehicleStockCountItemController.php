<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleStockCountItem;
use Illuminate\Http\Request;

class VehicleStockCountItemController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleStockCountItem::with(['item']);

        if ($request->filled('vehicle_stock_count_id')) {
            $query->where('vehicle_stock_count_id', $request->vehicle_stock_count_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_stock_count_id' => 'required',
            'item_id' => 'required',
            'system_qty' => 'nullable|numeric',
            'actual_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item = VehicleStockCountItem::create($validated);

        return response()->json($item->load('item'), 201);
    }

    public function show($id)
    {
        $item = VehicleStockCountItem::with(['item'])->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = VehicleStockCountItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_stock_count_id' => 'required',
            'item_id' => 'required',
            'system_qty' => 'nullable|numeric',
            'actual_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load('item'));
    }

    public function destroy($id)
    {
        $item = VehicleStockCountItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle stock count item deleted successfully']);
    }

    public function restore($id)
    {
        $item = VehicleStockCountItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load('item'));
    }

    public function forceDelete($id)
    {
        $item = VehicleStockCountItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle stock count item permanently deleted']);
    }
}
