<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{GpsTrackingPoint};
use App\Support\ValidationRules;

class GpsTrackingPointController extends Controller
{
    public function index(Request $request)
    {
        $query = GpsTrackingPoint::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('latitude', 'like', "%{$s}%")
                  ->orWhere('longitude', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('gps_tracking_point', 'create'));
        $gpsTrackingPoint = GpsTrackingPoint::create($data);
        return response()->json($gpsTrackingPoint, 201);
    }

    public function show($id)
    {
        return GpsTrackingPoint::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::findOrFail($id);
        $data = $request->validate(ValidationRules::for('gps_tracking_point', 'update', $gpsTrackingPoint));
        $gpsTrackingPoint->update($data);
        return $gpsTrackingPoint;
    }

    public function destroy($id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::findOrFail($id);
        $gpsTrackingPoint->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::withTrashed()->findOrFail($id);
        $gpsTrackingPoint->restore();
        return $gpsTrackingPoint;
    }

    public function forceDelete($id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::withTrashed()->findOrFail($id);
        $gpsTrackingPoint->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
