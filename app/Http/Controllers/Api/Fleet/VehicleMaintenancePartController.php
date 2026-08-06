<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenancePart;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMaintenancePartController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleMaintenancePart::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($maintenanceId = $request->input('vehicle_maintenance_id')) {
            $query->where('vehicle_maintenance_id', $maintenanceId);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_part', 'create'));
        $item = VehicleMaintenancePart::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleMaintenancePart::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleMaintenancePart::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_part', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleMaintenancePart::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
