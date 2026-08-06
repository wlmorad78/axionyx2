<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleBattery};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleBatteryController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleBattery::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('serial_number', 'like', "%{$s}%")
                  ->orWhere('brand', 'like', "%{$s}%");
            });
        }
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_battery', 'create'));
        $item = VehicleBattery::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleBattery::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleBattery::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_battery', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = VehicleBattery::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function restore($id)
    {
        $item = VehicleBattery::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    public function forceDelete($id)
    {
        $item = VehicleBattery::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
