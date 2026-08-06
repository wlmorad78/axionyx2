<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryShiftTransaction;
use Illuminate\Http\Request;

class TreasuryShiftTransactionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['shift'];
        $query = TreasuryShiftTransaction::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_shift_id) {
            $query->where('treasury_shift_id', $request->treasury_shift_id);
        }
        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_shift_id' => 'required',
            'transaction_type' => 'required|in:RECEIPT,PAYMENT,DEPOSIT,WITHDRAWAL,TRANSFER,ADJUSTMENT',
            'reference_type' => 'nullable',
            'reference_id' => 'nullable',
            'amount' => 'required|numeric',
            'transaction_datetime' => 'nullable|date',
            'notes' => 'nullable',
        ]);

        $transaction = TreasuryShiftTransaction::create($data);
        return response()->json($transaction, 201);
    }

    public function show($id)
    {
        $transaction = TreasuryShiftTransaction::with(['shift'])->findOrFail($id);
        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = TreasuryShiftTransaction::findOrFail($id);

        $data = $request->validate([
            'treasury_shift_id' => 'required',
            'transaction_type' => 'required|in:RECEIPT,PAYMENT,DEPOSIT,WITHDRAWAL,TRANSFER,ADJUSTMENT',
            'reference_type' => 'nullable',
            'reference_id' => 'nullable',
            'amount' => 'required|numeric',
            'transaction_datetime' => 'nullable|date',
            'notes' => 'nullable',
        ]);

        $transaction->update($data);
        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $transaction = TreasuryShiftTransaction::findOrFail($id);
        $transaction->delete();
        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $transaction = TreasuryShiftTransaction::onlyTrashed()->findOrFail($id);
        $transaction->restore();
        return response()->json($transaction);
    }

    public function forceDelete($id)
    {
        $transaction = TreasuryShiftTransaction::onlyTrashed()->findOrFail($id);
        $transaction->forceDelete();
        return response()->json(null, 204);
    }
}
