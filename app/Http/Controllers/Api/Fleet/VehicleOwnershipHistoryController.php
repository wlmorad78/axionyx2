<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleOwnershipHistory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleOwnershipHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleOwnershipHistory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('owner_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_ownership_history', 'create'));
        $item = VehicleOwnershipHistory::create($data);
        return response()->json($item, 201);
    }

    public function show($id)
    {
        return VehicleOwnershipHistory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $item = VehicleOwnershipHistory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_ownership_history', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleOwnershipHistory::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $item = VehicleOwnershipHistory::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function forceDelete($id)
    {
        $item = VehicleOwnershipHistory::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
