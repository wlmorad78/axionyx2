<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleStockCount;
use Illuminate\Http\Request;

class VehicleStockCountController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleStockCount::with(['items']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $counts = $query->paginate($request->get('per_page', 15));

        return response()->json($counts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'count_no' => 'required|unique:vehicle_stock_counts,count_no',
            'count_date' => 'required|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $count = VehicleStockCount::create($validated);

        return response()->json($count->load('items'), 201);
    }

    public function show($id)
    {
        $count = VehicleStockCount::with(['items'])->findOrFail($id);

        return response()->json($count);
    }

    public function update(Request $request, $id)
    {
        $count = VehicleStockCount::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'count_no' => 'required|unique:vehicle_stock_counts,count_no,' . $id,
            'count_date' => 'required|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $count->update($validated);

        return response()->json($count->load('items'));
    }

    public function destroy($id)
    {
        $count = VehicleStockCount::findOrFail($id);
        $count->delete();

        return response()->json(['message' => 'Vehicle stock count deleted successfully']);
    }

    public function restore($id)
    {
        $count = VehicleStockCount::withTrashed()->findOrFail($id);
        $count->restore();

        return response()->json($count->load('items'));
    }

    public function forceDelete($id)
    {
        $count = VehicleStockCount::withTrashed()->findOrFail($id);
        $count->forceDelete();

        return response()->json(['message' => 'Vehicle stock count permanently deleted']);
    }
}
