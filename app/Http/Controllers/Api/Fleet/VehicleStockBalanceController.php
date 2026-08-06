<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleStockBalance;
use Illuminate\Http\Request;

class VehicleStockBalanceController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleStockBalance::with(['item', 'vehicleWarehouse']);

        if ($request->filled('vehicle_warehouse_id')) {
            $query->where('vehicle_warehouse_id', $request->vehicle_warehouse_id);
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $balances = $query->paginate($request->get('per_page', 15));

        return response()->json($balances);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_warehouse_id' => 'required',
            'item_id' => 'required',
            'qty' => 'nullable|numeric',
            'average_cost' => 'nullable|numeric',
            'stock_value' => 'nullable|numeric',
        ]);

        $balance = VehicleStockBalance::create($validated);

        return response()->json($balance->load(['item', 'vehicleWarehouse']), 201);
    }

    public function show($id)
    {
        $balance = VehicleStockBalance::with(['item', 'vehicleWarehouse'])->findOrFail($id);

        return response()->json($balance);
    }

    public function update(Request $request, $id)
    {
        $balance = VehicleStockBalance::findOrFail($id);

        $validated = $request->validate([
            'vehicle_warehouse_id' => 'sometimes|required',
            'item_id' => 'sometimes|required',
            'qty' => 'nullable|numeric',
            'average_cost' => 'nullable|numeric',
            'stock_value' => 'nullable|numeric',
        ]);

        $balance->update($validated);

        return response()->json($balance->load(['item', 'vehicleWarehouse']));
    }

    public function destroy($id)
    {
        $balance = VehicleStockBalance::findOrFail($id);
        $balance->delete();

        return response()->json(['message' => 'Vehicle stock balance deleted successfully']);
    }

    public function restore($id)
    {
        $balance = VehicleStockBalance::onlyTrashed()->findOrFail($id);
        $balance->restore();

        return response()->json($balance->load(['item', 'vehicleWarehouse']));
    }

    public function forceDelete($id)
    {
        $balance = VehicleStockBalance::onlyTrashed()->findOrFail($id);
        $balance->forceDelete();

        return response()->json(['message' => 'Vehicle stock balance permanently deleted']);
    }
}
