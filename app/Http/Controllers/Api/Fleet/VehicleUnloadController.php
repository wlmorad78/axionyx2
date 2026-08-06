<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleUnload;
use Illuminate\Http\Request;

class VehicleUnloadController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleUnload::with(['items', 'vehicle', 'returnOrder']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('unload_date_from')) {
            $query->where('unload_date', '>=', $request->unload_date_from);
        }
        if ($request->filled('unload_date_to')) {
            $query->where('unload_date', '<=', $request->unload_date_to);
        }

        $unloads = $query->paginate($request->get('per_page', 15));

        return response()->json($unloads);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'return_order_id' => 'nullable',
            'unload_no' => 'required|unique:vehicle_unloads,unload_no',
            'unload_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $unload = VehicleUnload::create($validated);

        return response()->json($unload->load(['items', 'vehicle', 'returnOrder']), 201);
    }

    public function show($id)
    {
        $unload = VehicleUnload::with(['items', 'vehicle', 'returnOrder'])->findOrFail($id);

        return response()->json($unload);
    }

    public function update(Request $request, $id)
    {
        $unload = VehicleUnload::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'sometimes|required',
            'return_order_id' => 'nullable',
            'unload_no' => 'sometimes|required|unique:vehicle_unloads,unload_no,' . $id,
            'unload_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $unload->update($validated);

        return response()->json($unload->load(['items', 'vehicle', 'returnOrder']));
    }

    public function destroy($id)
    {
        $unload = VehicleUnload::findOrFail($id);
        $unload->delete();

        return response()->json(['message' => 'Vehicle unload deleted successfully']);
    }

    public function restore($id)
    {
        $unload = VehicleUnload::onlyTrashed()->findOrFail($id);
        $unload->restore();

        return response()->json($unload->load(['items', 'vehicle', 'returnOrder']));
    }

    public function forceDelete($id)
    {
        $unload = VehicleUnload::onlyTrashed()->findOrFail($id);
        $unload->forceDelete();

        return response()->json(['message' => 'Vehicle unload permanently deleted']);
    }
}
