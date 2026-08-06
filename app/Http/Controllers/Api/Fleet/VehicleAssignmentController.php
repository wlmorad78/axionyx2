<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleAssignment::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_assignment', 'create'));
        $vehicleAssignment = VehicleAssignment::create($data);
        return response()->json($vehicleAssignment, 201);
    }

    public function show($id)
    {
        return VehicleAssignment::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $vehicleAssignment = VehicleAssignment::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_assignment', 'update', $vehicleAssignment));
        $vehicleAssignment->update($data);
        return $vehicleAssignment;
    }

    public function destroy($id)
    {
        $vehicleAssignment = VehicleAssignment::findOrFail($id);
        $vehicleAssignment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $vehicleAssignment = VehicleAssignment::withTrashed()->findOrFail($id);
        $vehicleAssignment->restore();
        return $vehicleAssignment;
    }

    public function forceDelete($id)
    {
        $vehicleAssignment = VehicleAssignment::withTrashed()->findOrFail($id);
        $vehicleAssignment->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
