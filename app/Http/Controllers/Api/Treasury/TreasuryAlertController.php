<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryAlert;
use Illuminate\Http\Request;

class TreasuryAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryAlert::with(['treasury']);

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->orderBy('alert_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($alerts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'alert_type' => 'required|in:LOW_CASH,HIGH_CASH,SHORTAGE,OVERAGE',
            'alert_date' => 'required|date',
            'message' => 'required|string',
            'status' => 'required|string|max:255',
        ]);

        $alert = TreasuryAlert::create($validated);

        return response()->json($alert->load('treasury'), 201);
    }

    public function show($id)
    {
        $alert = TreasuryAlert::with(['treasury'])->findOrFail($id);

        return response()->json($alert);
    }

    public function update(Request $request, $id)
    {
        $alert = TreasuryAlert::findOrFail($id);

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'alert_type' => 'required|in:LOW_CASH,HIGH_CASH,SHORTAGE,OVERAGE',
            'alert_date' => 'required|date',
            'message' => 'required|string',
            'status' => 'required|string|max:255',
        ]);

        $alert->update($validated);

        return response()->json($alert->load('treasury'));
    }

    public function destroy($id)
    {
        $alert = TreasuryAlert::findOrFail($id);
        $alert->delete();

        return response()->json(['message' => 'Treasury alert deleted successfully']);
    }

    public function restore($id)
    {
        $alert = TreasuryAlert::onlyTrashed()->findOrFail($id);
        $alert->restore();

        return response()->json($alert->load('treasury'));
    }

    public function forceDelete($id)
    {
        $alert = TreasuryAlert::onlyTrashed()->findOrFail($id);
        $alert->forceDelete();

        return response()->json(['message' => 'Treasury alert permanently deleted']);
    }
}
