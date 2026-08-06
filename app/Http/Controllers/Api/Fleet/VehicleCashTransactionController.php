<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleCashTransaction;
use Illuminate\Http\Request;

class VehicleCashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleCashTransaction::with(['cashAccount']);

        if ($request->filled('vehicle_cash_account_id')) {
            $query->where('vehicle_cash_account_id', $request->vehicle_cash_account_id);
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
            'vehicle_cash_account_id' => 'required',
            'transaction_type' => 'required|in:COLLECTION,EXPENSE,DEPOSIT,SETTLEMENT',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $transaction = VehicleCashTransaction::create($validated);

        return response()->json($transaction->load('cashAccount'), 201);
    }

    public function show($id)
    {
        $transaction = VehicleCashTransaction::with(['cashAccount'])->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = VehicleCashTransaction::findOrFail($id);

        $validated = $request->validate([
            'vehicle_cash_account_id' => 'required',
            'transaction_type' => 'required|in:COLLECTION,EXPENSE,DEPOSIT,SETTLEMENT',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load('cashAccount'));
    }

    public function destroy($id)
    {
        $transaction = VehicleCashTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Vehicle cash transaction deleted successfully']);
    }

    public function restore($id)
    {
        $transaction = VehicleCashTransaction::withTrashed()->findOrFail($id);
        $transaction->restore();

        return response()->json($transaction->load('cashAccount'));
    }

    public function forceDelete($id)
    {
        $transaction = VehicleCashTransaction::withTrashed()->findOrFail($id);
        $transaction->forceDelete();

        return response()->json(['message' => 'Vehicle cash transaction permanently deleted']);
    }
}
