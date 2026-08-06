<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleInventoryTransaction;
use Illuminate\Http\Request;

class VehicleInventoryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleInventoryTransaction::with(['items', 'vehicleWarehouse']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('vehicle_warehouse_id')) {
            $query->where('vehicle_warehouse_id', $request->vehicle_warehouse_id);
        }
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->filled('transaction_date_from')) {
            $query->where('transaction_date', '>=', $request->transaction_date_from);
        }
        if ($request->filled('transaction_date_to')) {
            $query->where('transaction_date', '<=', $request->transaction_date_to);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
            'vehicle_warehouse_id' => 'required',
            'transaction_no' => 'required|unique:vehicle_inventory_transactions,transaction_no',
            'transaction_type' => 'required|in:LOAD,SALE,RETURN,TRANSFER,ADJUSTMENT,COUNT',
            'transaction_date' => 'required|date',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable',
            'notes' => 'nullable|string',
            'created_by' => 'nullable',
        ]);

        $transaction = VehicleInventoryTransaction::create($validated);

        return response()->json($transaction->load(['items', 'vehicleWarehouse']), 201);
    }

    public function show($id)
    {
        $transaction = VehicleInventoryTransaction::with(['items', 'vehicleWarehouse'])->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = VehicleInventoryTransaction::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'sometimes|required',
            'branch_id' => 'nullable',
            'vehicle_warehouse_id' => 'sometimes|required',
            'transaction_no' => 'sometimes|required|unique:vehicle_inventory_transactions,transaction_no,' . $id,
            'transaction_type' => 'sometimes|required|in:LOAD,SALE,RETURN,TRANSFER,ADJUSTMENT,COUNT',
            'transaction_date' => 'sometimes|required|date',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable',
            'notes' => 'nullable|string',
            'created_by' => 'nullable',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load(['items', 'vehicleWarehouse']));
    }

    public function destroy($id)
    {
        $transaction = VehicleInventoryTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Vehicle inventory transaction deleted successfully']);
    }

    public function restore($id)
    {
        $transaction = VehicleInventoryTransaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();

        return response()->json($transaction->load(['items', 'vehicleWarehouse']));
    }

    public function forceDelete($id)
    {
        $transaction = VehicleInventoryTransaction::onlyTrashed()->findOrFail($id);
        $transaction->forceDelete();

        return response()->json(['message' => 'Vehicle inventory transaction permanently deleted']);
    }
}
