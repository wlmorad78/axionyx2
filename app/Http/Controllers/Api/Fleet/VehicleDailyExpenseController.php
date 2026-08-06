<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleDailyExpense;
use Illuminate\Http\Request;

class VehicleDailyExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleDailyExpense::with(['vehicle']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        if ($request->filled('expense_date_from')) {
            $query->where('expense_date', '>=', $request->expense_date_from);
        }

        if ($request->filled('expense_date_to')) {
            $query->where('expense_date', '<=', $request->expense_date_to);
        }

        $expenses = $query->paginate($request->get('per_page', 15));

        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'expense_date' => 'required|date',
            'expense_type' => 'required|in:FUEL,TOLL,MAINTENANCE,PARKING,OTHER',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'created_by' => 'nullable',
        ]);

        $expense = VehicleDailyExpense::create($validated);

        return response()->json($expense->load('vehicle'), 201);
    }

    public function show($id)
    {
        $expense = VehicleDailyExpense::with(['vehicle'])->findOrFail($id);

        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = VehicleDailyExpense::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'expense_date' => 'required|date',
            'expense_type' => 'required|in:FUEL,TOLL,MAINTENANCE,PARKING,OTHER',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'created_by' => 'nullable',
        ]);

        $expense->update($validated);

        return response()->json($expense->load('vehicle'));
    }

    public function destroy($id)
    {
        $expense = VehicleDailyExpense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Vehicle daily expense deleted successfully']);
    }

    public function restore($id)
    {
        $expense = VehicleDailyExpense::withTrashed()->findOrFail($id);
        $expense->restore();

        return response()->json($expense->load('vehicle'));
    }

    public function forceDelete($id)
    {
        $expense = VehicleDailyExpense::withTrashed()->findOrFail($id);
        $expense->forceDelete();

        return response()->json(['message' => 'Vehicle daily expense permanently deleted']);
    }
}
