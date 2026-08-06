<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenancePlan;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMaintenancePlanController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleMaintenancePlan::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('plan_name', 'like', "%{$s}%")
                  ->orWhere('maintenance_type', 'like', "%{$s}%");
            });
        }

        if ($vehicleId = $request->input('vehicle_id')) {
            $query->where('vehicle_id', $vehicleId);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_plan', 'create'));
        $item = VehicleMaintenancePlan::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleMaintenancePlan::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleMaintenancePlan::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_plan', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleMaintenancePlan::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $item = VehicleMaintenancePlan::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function forceDelete($id)
    {
        $item = VehicleMaintenancePlan::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
