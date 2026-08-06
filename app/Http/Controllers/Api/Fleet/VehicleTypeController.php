<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\VehicleType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active') !== null) $query->where('is_active', $request->boolean('is_active'));

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderBy('sort_order')->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_type', 'create'));
        $vehicleType = VehicleType::create($data);
        return response()->json($vehicleType, 201);
    }

    public function show($id)
    {
        return VehicleType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $vehicleType = VehicleType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_type', 'update', $vehicleType));
        $vehicleType->update($data);
        return $vehicleType;
    }

    public function destroy($id)
    {
        $vehicleType = VehicleType::findOrFail($id);
        $vehicleType->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $vehicleType = VehicleType::withTrashed()->findOrFail($id);
        $vehicleType->restore();
        return $vehicleType;
    }

    public function forceDelete($id)
    {
        $vehicleType = VehicleType::withTrashed()->findOrFail($id);
        $vehicleType->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
