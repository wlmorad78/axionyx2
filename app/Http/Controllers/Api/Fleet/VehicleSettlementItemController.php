<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleSettlementItem;
use Illuminate\Http\Request;

class VehicleSettlementItemController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleSettlementItem::with(['item']);

        if ($request->filled('vehicle_settlement_id')) {
            $query->where('vehicle_settlement_id', $request->vehicle_settlement_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_settlement_id' => 'required',
            'item_id' => 'required',
            'opening_qty' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'sold_qty' => 'nullable|numeric',
            'returned_qty' => 'nullable|numeric',
            'closing_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item = VehicleSettlementItem::create($validated);

        return response()->json($item->load('item'), 201);
    }

    public function show($id)
    {
        $item = VehicleSettlementItem::with(['item'])->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = VehicleSettlementItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_settlement_id' => 'required',
            'item_id' => 'required',
            'opening_qty' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'sold_qty' => 'nullable|numeric',
            'returned_qty' => 'nullable|numeric',
            'closing_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load('item'));
    }

    public function destroy($id)
    {
        $item = VehicleSettlementItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle settlement item deleted successfully']);
    }

    public function restore($id)
    {
        $item = VehicleSettlementItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load('item'));
    }

    public function forceDelete($id)
    {
        $item = VehicleSettlementItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle settlement item permanently deleted']);
    }
}
