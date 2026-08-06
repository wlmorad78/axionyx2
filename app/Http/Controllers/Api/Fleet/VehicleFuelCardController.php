<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleFuelCard};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleFuelCardController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleFuelCard::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('card_number', 'like', "%{$s}%");
            });
        }
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }
        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->input('driver_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_fuel_card', 'create'));
        $item = VehicleFuelCard::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleFuelCard::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleFuelCard::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_fuel_card', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = VehicleFuelCard::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function restore($id)
    {
        $item = VehicleFuelCard::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    public function forceDelete($id)
    {
        $item = VehicleFuelCard::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
