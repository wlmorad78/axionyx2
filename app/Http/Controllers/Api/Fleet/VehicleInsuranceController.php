<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleInsurance};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleInsuranceController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleInsurance::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('policy_number', 'like', "%{$s}%")
                    ->orWhere('insurance_company', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_insurance', 'create'));
        $item = VehicleInsurance::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleInsurance::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleInsurance::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_insurance', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleInsurance::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $item = VehicleInsurance::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function forceDelete($id)
    {
        $item = VehicleInsurance::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
