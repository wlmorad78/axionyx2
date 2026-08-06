<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Geofence;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    public function index(Request $request)
    {
        $query = Geofence::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('geofence', 'create'));
        $item = Geofence::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return Geofence::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = Geofence::findOrFail($id);
        $data = $request->validate(ValidationRules::for('geofence', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = Geofence::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $item = Geofence::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function forceDelete($id)
    {
        $item = Geofence::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
