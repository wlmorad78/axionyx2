<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleIdleTime;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleIdleTimeController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleIdleTime::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_idle_time', 'create'));
        $item = VehicleIdleTime::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleIdleTime::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleIdleTime::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_idle_time', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleIdleTime::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
