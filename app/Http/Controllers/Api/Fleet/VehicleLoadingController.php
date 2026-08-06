<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleLoading;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleLoadingController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleLoading::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('loading_date', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_loading', 'create'));
        $vehicleLoading = VehicleLoading::create($data);
        return response()->json($vehicleLoading, 201);
    }

    public function show($id)
    {
        return VehicleLoading::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $vehicleLoading = VehicleLoading::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_loading', 'update', $vehicleLoading));
        $vehicleLoading->update($data);
        return $vehicleLoading;
    }

    public function destroy($id)
    {
        $vehicleLoading = VehicleLoading::findOrFail($id);
        $vehicleLoading->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $vehicleLoading = VehicleLoading::withTrashed()->findOrFail($id);
        $vehicleLoading->restore();
        return $vehicleLoading;
    }

    public function forceDelete($id)
    {
        $vehicleLoading = VehicleLoading::withTrashed()->findOrFail($id);
        $vehicleLoading->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
