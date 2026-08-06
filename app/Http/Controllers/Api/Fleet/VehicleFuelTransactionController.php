<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleFuelTransaction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleFuelTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleFuelTransaction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_fuel_transaction', 'create'));
        $vehicleFuelTransaction = VehicleFuelTransaction::create($data);
        return response()->json($vehicleFuelTransaction, 201);
    }

    public function show($id)
    {
        return VehicleFuelTransaction::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_fuel_transaction', 'update', $vehicleFuelTransaction));
        $vehicleFuelTransaction->update($data);
        return $vehicleFuelTransaction;
    }

    public function destroy($id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::findOrFail($id);
        $vehicleFuelTransaction->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::withTrashed()->findOrFail($id);
        $vehicleFuelTransaction->restore();
        return $vehicleFuelTransaction;
    }

    public function forceDelete($id)
    {
        $vehicleFuelTransaction = VehicleFuelTransaction::withTrashed()->findOrFail($id);
        $vehicleFuelTransaction->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
