<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCustodyTransaction;
use Illuminate\Http\Request;

class TreasuryCustodyTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryCustodyTransaction::with(['custody']);

        if ($request->filled('treasury_custody_id')) {
            $query->where('treasury_custody_id', $request->treasury_custody_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_custody_id' => 'required|exists:treasury_custodies,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:ISSUE,RETURN,SETTLEMENT,ADJUSTMENT',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $transaction = TreasuryCustodyTransaction::create($validated);

        return response()->json($transaction->load('custody'), 201);
    }

    public function show($id)
    {
        $transaction = TreasuryCustodyTransaction::with(['custody'])->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = TreasuryCustodyTransaction::findOrFail($id);

        $validated = $request->validate([
            'treasury_custody_id' => 'required|exists:treasury_custodies,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:ISSUE,RETURN,SETTLEMENT,ADJUSTMENT',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load('custody'));
    }

    public function destroy($id)
    {
        $transaction = TreasuryCustodyTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Treasury custody transaction deleted successfully']);
    }

    public function restore($id)
    {
        $transaction = TreasuryCustodyTransaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();

        return response()->json($transaction->load('custody'));
    }

    public function forceDelete($id)
    {
        $transaction = TreasuryCustodyTransaction::onlyTrashed()->findOrFail($id);
        $transaction->forceDelete();

        return response()->json(['message' => 'Treasury custody transaction permanently deleted']);
    }
}
