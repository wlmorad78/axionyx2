<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleMaintenance::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('maintenance_type', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_maintenance', 'create'));
        $vehicleMaintenance = VehicleMaintenance::create($data);
        return response()->json($vehicleMaintenance, 201);
    }

    public function show($id)
    {
        return VehicleMaintenance::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $vehicleMaintenance = VehicleMaintenance::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_maintenance', 'update', $vehicleMaintenance));
        $vehicleMaintenance->update($data);
        return $vehicleMaintenance;
    }

    public function destroy($id)
    {
        $vehicleMaintenance = VehicleMaintenance::findOrFail($id);
        $vehicleMaintenance->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $vehicleMaintenance = VehicleMaintenance::withTrashed()->findOrFail($id);
        $vehicleMaintenance->restore();
        return $vehicleMaintenance;
    }

    public function forceDelete($id)
    {
        $vehicleMaintenance = VehicleMaintenance::withTrashed()->findOrFail($id);
        $vehicleMaintenance->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
