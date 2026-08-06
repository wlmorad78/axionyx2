<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleWarehouse;
use Illuminate\Http\Request;

class VehicleWarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleWarehouse::with(['vehicle', 'warehouse']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $warehouses = $query->paginate($request->get('per_page', 15));

        return response()->json($warehouses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'vehicle_id' => 'required',
            'warehouse_id' => 'required',
            'code' => 'required|string|max:50|unique:vehicle_warehouses,code',
            'is_active' => 'boolean',
        ]);

        $warehouse = VehicleWarehouse::create($validated);

        return response()->json($warehouse->load(['vehicle', 'warehouse']), 201);
    }

    public function show($id)
    {
        $warehouse = VehicleWarehouse::with(['vehicle', 'warehouse'])->findOrFail($id);

        return response()->json($warehouse);
    }

    public function update(Request $request, $id)
    {
        $warehouse = VehicleWarehouse::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'sometimes|required',
            'vehicle_id' => 'sometimes|required',
            'warehouse_id' => 'sometimes|required',
            'code' => 'sometimes|required|string|max:50|unique:vehicle_warehouses,code,' . $id,
            'is_active' => 'boolean',
        ]);

        $warehouse->update($validated);

        return response()->json($warehouse->load(['vehicle', 'warehouse']));
    }

    public function destroy($id)
    {
        $warehouse = VehicleWarehouse::findOrFail($id);
        $warehouse->delete();

        return response()->json(['message' => 'Vehicle warehouse deleted successfully']);
    }

    public function restore($id)
    {
        $warehouse = VehicleWarehouse::onlyTrashed()->findOrFail($id);
        $warehouse->restore();

        return response()->json($warehouse->load(['vehicle', 'warehouse']));
    }

    public function forceDelete($id)
    {
        $warehouse = VehicleWarehouse::onlyTrashed()->findOrFail($id);
        $warehouse->forceDelete();

        return response()->json(['message' => 'Vehicle warehouse permanently deleted']);
    }
}
