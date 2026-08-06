<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleFuelPrice};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleFuelPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleFuelPrice::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('fuel_type', 'like', "%{$s}%");
            });
        }
        if ($request->has('fuel_station_id')) {
            $query->where('fuel_station_id', $request->input('fuel_station_id'));
        }
        if ($request->has('fuel_type')) {
            $query->where('fuel_type', $request->input('fuel_type'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_fuel_price', 'create'));
        $item = VehicleFuelPrice::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleFuelPrice::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleFuelPrice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_fuel_price', 'update', $item));
        $item->update($data);
        return $item;
    }
    public function destroy($id)
    {
        $item = VehicleFuelPrice::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
