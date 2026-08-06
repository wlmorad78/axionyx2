<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\TreasuryCustody;
use Illuminate\Http\Request;

class TreasuryCustodyController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryCustody::with(['employee', 'treasury', 'transactions']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $custodies = $query->orderBy('issue_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($custodies);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'treasury_id' => 'required|exists:treasuries,id',
            'custody_no' => 'required|unique:treasury_custodies,custody_no',
            'issue_date' => 'required|date',
            'amount' => 'required|numeric',
            'status' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $custody = TreasuryCustody::create($validated);

        return response()->json($custody->load(['employee', 'treasury', 'transactions']), 201);
    }

    public function show($id)
    {
        $custody = TreasuryCustody::with(['employee', 'treasury', 'transactions'])->findOrFail($id);

        return response()->json($custody);
    }

    public function update(Request $request, $id)
    {
        $custody = TreasuryCustody::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'treasury_id' => 'required|exists:treasuries,id',
            'custody_no' => 'required|unique:treasury_custodies,custody_no,' . $id,
            'issue_date' => 'required|date',
            'amount' => 'required|numeric',
            'status' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $custody->update($validated);

        return response()->json($custody->load(['employee', 'treasury', 'transactions']));
    }

    public function destroy($id)
    {
        $custody = TreasuryCustody::findOrFail($id);
        $custody->delete();

        return response()->json(['message' => 'Treasury custody deleted successfully']);
    }

    public function restore($id)
    {
        $custody = TreasuryCustody::onlyTrashed()->findOrFail($id);
        $custody->restore();

        return response()->json($custody->load(['employee', 'treasury', 'transactions']));
    }

    public function forceDelete($id)
    {
        $custody = TreasuryCustody::onlyTrashed()->findOrFail($id);
        $custody->forceDelete();

        return response()->json(['message' => 'Treasury custody permanently deleted']);
    }
}
