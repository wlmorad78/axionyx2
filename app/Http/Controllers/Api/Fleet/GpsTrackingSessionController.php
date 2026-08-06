<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{GpsTrackingSession};
use App\Support\ValidationRules;

class GpsTrackingSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = GpsTrackingSession::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('start_time', 'like', "%{$s}%")
                  ->orWhere('end_time', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('gps_tracking_session', 'create'));
        $gpsTrackingSession = GpsTrackingSession::create($data);
        return response()->json($gpsTrackingSession, 201);
    }

    public function show($id)
    {
        return GpsTrackingSession::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $gpsTrackingSession = GpsTrackingSession::findOrFail($id);
        $data = $request->validate(ValidationRules::for('gps_tracking_session', 'update', $gpsTrackingSession));
        $gpsTrackingSession->update($data);
        return $gpsTrackingSession;
    }

    public function destroy($id)
    {
        $gpsTrackingSession = GpsTrackingSession::findOrFail($id);
        $gpsTrackingSession->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $gpsTrackingSession = GpsTrackingSession::withTrashed()->findOrFail($id);
        $gpsTrackingSession->restore();
        return $gpsTrackingSession;
    }

    public function forceDelete($id)
    {
        $gpsTrackingSession = GpsTrackingSession::withTrashed()->findOrFail($id);
        $gpsTrackingSession->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
