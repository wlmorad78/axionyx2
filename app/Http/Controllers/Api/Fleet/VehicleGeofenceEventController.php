<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleGeofenceEvent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleGeofenceEventController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleGeofenceEvent::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('event_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('geofence_id')) $query->where('geofence_id', $request->geofence_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_geofence_event', 'create'));
        $item = VehicleGeofenceEvent::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleGeofenceEvent::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleGeofenceEvent::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_geofence_event', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleGeofenceEvent::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
