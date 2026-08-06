<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleLoadItem;
use Illuminate\Http\Request;

class VehicleLoadItemController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleLoadItem::with(['item', 'unit']);

        if ($request->filled('vehicle_load_id')) {
            $query->where('vehicle_load_id', $request->vehicle_load_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_load_id' => 'required',
            'item_id' => 'required',
            'unit_id' => 'nullable',
            'qty' => 'required|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $item = VehicleLoadItem::create($validated);

        return response()->json($item->load(['item', 'unit']), 201);
    }

    public function show($id)
    {
        $item = VehicleLoadItem::with(['item', 'unit'])->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = VehicleLoadItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_load_id' => 'sometimes|required',
            'item_id' => 'sometimes|required',
            'unit_id' => 'nullable',
            'qty' => 'sometimes|required|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load(['item', 'unit']));
    }

    public function destroy($id)
    {
        $item = VehicleLoadItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle load item deleted successfully']);
    }

    public function restore($id)
    {
        $item = VehicleLoadItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load(['item', 'unit']));
    }

    public function forceDelete($id)
    {
        $item = VehicleLoadItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle load item permanently deleted']);
    }
}
