<?php
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleDailyShift;
use Illuminate\Http\Request;

class VehicleDailyShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleDailyShift::with(['vehicle', 'driver', 'salesRep']);

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        if ($request->filled('sales_rep_id')) $query->where('sales_rep_id', $request->sales_rep_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('shift_date_from')) $query->where('shift_date', '>=', $request->shift_date_from);
        if ($request->filled('shift_date_to')) $query->where('shift_date', '<=', $request->shift_date_to);

        return response()->json($query->latest('shift_date')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'sales_rep_id' => 'nullable|exists:users,id',
            'shift_date' => 'required|date',
            'start_km' => 'nullable|numeric|min:0',
            'end_km' => 'nullable|numeric|min:0|gte:start_km',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:255',
            'status' => 'required|in:IN_PROGRESS,COMPLETED',
        ]);

        $shift = VehicleDailyShift::create($validated);

        return response()->json($shift->load(['vehicle', 'driver', 'salesRep']), 201);
    }

    public function show($id)
    {
        return response()->json(VehicleDailyShift::with(['vehicle', 'driver', 'salesRep'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $shift = VehicleDailyShift::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'sales_rep_id' => 'nullable|exists:users,id',
            'shift_date' => 'required|date',
            'start_km' => 'nullable|numeric|min:0',
            'end_km' => 'nullable|numeric|min:0',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:255',
            'status' => 'required|in:IN_PROGRESS,COMPLETED',
        ]);

        $shift->update($validated);

        return response()->json($shift->load(['vehicle', 'driver', 'salesRep']));
    }

    public function destroy($id)
    {
        VehicleDailyShift::findOrFail($id)->delete();
        return response()->json(['message' => 'Vehicle daily shift deleted successfully']);
    }

    public function restore($id)
    {
        $shift = VehicleDailyShift::withTrashed()->findOrFail($id);
        $shift->restore();
        return response()->json($shift->load(['vehicle', 'driver', 'salesRep']));
    }

    public function forceDelete($id)
    {
        VehicleDailyShift::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Vehicle daily shift permanently deleted']);
    }
}
