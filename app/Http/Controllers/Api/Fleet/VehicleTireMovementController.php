<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleTireMovement};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleTireMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleTireMovement::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('movement_type', 'like', "%{$s}%");
            });
        }
        if ($request->has('tire_id')) {
            $query->where('tire_id', $request->input('tire_id'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_tire_movement', 'create'));
        $item = VehicleTireMovement::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleTireMovement::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleTireMovement::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_tire_movement', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = VehicleTireMovement::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
