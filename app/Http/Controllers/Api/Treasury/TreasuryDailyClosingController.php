<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryDailyClosing;
use Illuminate\Http\Request;

class TreasuryDailyClosingController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryDailyClosing::with(['details', 'treasury', 'approver']);

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('closing_date_from')) {
            $query->where('closing_date', '>=', $request->closing_date_from);
        }

        if ($request->filled('closing_date_to')) {
            $query->where('closing_date', '<=', $request->closing_date_to);
        }

        $closings = $query->orderBy('closing_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($closings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'closing_date' => 'required|date',
            'opening_balance' => 'required|numeric',
            'receipts_total' => 'required|numeric',
            'payments_total' => 'required|numeric',
            'transfers_in' => 'required|numeric',
            'transfers_out' => 'required|numeric',
            'expected_balance' => 'required|numeric',
            'actual_balance' => 'required|numeric',
            'difference_amount' => 'required|numeric',
            'status' => 'required|in:DRAFT,PENDING_APPROVAL,APPROVED,REJECTED',
            'approved_by' => 'nullable|exists:users,id',
        ]);

        $closing = TreasuryDailyClosing::create($validated);

        return response()->json($closing->load(['details', 'treasury', 'approver']), 201);
    }

    public function show($id)
    {
        $closing = TreasuryDailyClosing::with(['details', 'treasury', 'approver'])->findOrFail($id);

        return response()->json($closing);
    }

    public function update(Request $request, $id)
    {
        $closing = TreasuryDailyClosing::findOrFail($id);

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'closing_date' => 'required|date',
            'opening_balance' => 'required|numeric',
            'receipts_total' => 'required|numeric',
            'payments_total' => 'required|numeric',
            'transfers_in' => 'required|numeric',
            'transfers_out' => 'required|numeric',
            'expected_balance' => 'required|numeric',
            'actual_balance' => 'required|numeric',
            'difference_amount' => 'required|numeric',
            'status' => 'required|in:DRAFT,PENDING_APPROVAL,APPROVED,REJECTED',
            'approved_by' => 'nullable|exists:users,id',
        ]);

        $closing->update($validated);

        return response()->json($closing->load(['details', 'treasury', 'approver']));
    }

    public function destroy($id)
    {
        $closing = TreasuryDailyClosing::findOrFail($id);
        $closing->delete();

        return response()->json(['message' => 'Treasury daily closing deleted successfully']);
    }

    public function restore($id)
    {
        $closing = TreasuryDailyClosing::onlyTrashed()->findOrFail($id);
        $closing->restore();

        return response()->json($closing->load(['details', 'treasury', 'approver']));
    }

    public function forceDelete($id)
    {
        $closing = TreasuryDailyClosing::onlyTrashed()->findOrFail($id);
        $closing->forceDelete();

        return response()->json(['message' => 'Treasury daily closing permanently deleted']);
    }
}
