<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleWorkOrder;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleWorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleWorkOrder::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('work_order_no', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%")
                  ->orWhere('priority', 'like', "%{$s}%");
            });
        }

        if ($vehicleId = $request->input('vehicle_id')) {
            $query->where('vehicle_id', $vehicleId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_work_order', 'create'));
        $item = VehicleWorkOrder::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleWorkOrder::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleWorkOrder::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_work_order', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleWorkOrder::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $item = VehicleWorkOrder::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function forceDelete($id)
    {
        $item = VehicleWorkOrder::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
