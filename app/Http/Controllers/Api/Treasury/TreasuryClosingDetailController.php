<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryClosingDetail;
use Illuminate\Http\Request;

class TreasuryClosingDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryClosingDetail::with(['closing']);

        if ($request->filled('treasury_daily_closing_id')) {
            $query->where('treasury_daily_closing_id', $request->treasury_daily_closing_id);
        }

        $details = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($details);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_daily_closing_id' => 'required|exists:treasury_daily_closings,id',
            'transaction_type' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
        ]);

        $detail = TreasuryClosingDetail::create($validated);

        return response()->json($detail->load('closing'), 201);
    }

    public function show($id)
    {
        $detail = TreasuryClosingDetail::with(['closing'])->findOrFail($id);

        return response()->json($detail);
    }

    public function update(Request $request, $id)
    {
        $detail = TreasuryClosingDetail::findOrFail($id);

        $validated = $request->validate([
            'treasury_daily_closing_id' => 'required|exists:treasury_daily_closings,id',
            'transaction_type' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
        ]);

        $detail->update($validated);

        return response()->json($detail->load('closing'));
    }

    public function destroy($id)
    {
        $detail = TreasuryClosingDetail::findOrFail($id);
        $detail->delete();

        return response()->json(['message' => 'Treasury closing detail deleted successfully']);
    }

    public function restore($id)
    {
        $detail = TreasuryClosingDetail::onlyTrashed()->findOrFail($id);
        $detail->restore();

        return response()->json($detail->load('closing'));
    }

    public function forceDelete($id)
    {
        $detail = TreasuryClosingDetail::onlyTrashed()->findOrFail($id);
        $detail->forceDelete();

        return response()->json(['message' => 'Treasury closing detail permanently deleted']);
    }
}
