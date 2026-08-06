<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleExpense;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleExpense::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('expense_type', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_expense', 'create'));
        $vehicleExpense = VehicleExpense::create($data);
        return response()->json($vehicleExpense, 201);
    }

    public function show($id)
    {
        return VehicleExpense::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $vehicleExpense = VehicleExpense::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_expense', 'update', $vehicleExpense));
        $vehicleExpense->update($data);
        return $vehicleExpense;
    }

    public function destroy($id)
    {
        $vehicleExpense = VehicleExpense::findOrFail($id);
        $vehicleExpense->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $vehicleExpense = VehicleExpense::withTrashed()->findOrFail($id);
        $vehicleExpense->restore();
        return $vehicleExpense;
    }

    public function forceDelete($id)
    {
        $vehicleExpense = VehicleExpense::withTrashed()->findOrFail($id);
        $vehicleExpense->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
