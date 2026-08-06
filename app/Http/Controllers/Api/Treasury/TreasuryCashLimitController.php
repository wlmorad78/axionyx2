<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCashLimit;
use Illuminate\Http\Request;

class TreasuryCashLimitController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryCashLimit::with(['treasury']);

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        $limits = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($limits);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'minimum_limit' => 'required|numeric',
            'maximum_limit' => 'required|numeric',
            'alert_limit' => 'required|numeric',
        ]);

        $limit = TreasuryCashLimit::create($validated);

        return response()->json($limit->load('treasury'), 201);
    }

    public function show($id)
    {
        $limit = TreasuryCashLimit::with(['treasury'])->findOrFail($id);

        return response()->json($limit);
    }

    public function update(Request $request, $id)
    {
        $limit = TreasuryCashLimit::findOrFail($id);

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'minimum_limit' => 'required|numeric',
            'maximum_limit' => 'required|numeric',
            'alert_limit' => 'required|numeric',
        ]);

        $limit->update($validated);

        return response()->json($limit->load('treasury'));
    }

    public function destroy($id)
    {
        $limit = TreasuryCashLimit::findOrFail($id);
        $limit->delete();

        return response()->json(['message' => 'Treasury cash limit deleted successfully']);
    }

    public function restore($id)
    {
        $limit = TreasuryCashLimit::onlyTrashed()->findOrFail($id);
        $limit->restore();

        return response()->json($limit->load('treasury'));
    }

    public function forceDelete($id)
    {
        $limit = TreasuryCashLimit::onlyTrashed()->findOrFail($id);
        $limit->forceDelete();

        return response()->json(['message' => 'Treasury cash limit permanently deleted']);
    }
}
