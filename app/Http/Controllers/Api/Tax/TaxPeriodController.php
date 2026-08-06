<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxPeriod;
use Illuminate\Http\Request;

class TaxPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxPeriod::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('period_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $taxPeriods = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($taxPeriods);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:OPEN,CLOSED,SUBMITTED',
        ]);

        $taxPeriod = TaxPeriod::create($validated);

        return response()->json($taxPeriod, 201);
    }

    public function show(TaxPeriod $taxPeriod)
    {
        return response()->json($taxPeriod);
    }

    public function update(Request $request, TaxPeriod $taxPeriod)
    {
        $validated = $request->validate([
            'period_name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:OPEN,CLOSED,SUBMITTED',
        ]);

        $taxPeriod->update($validated);

        return response()->json($taxPeriod);
    }

    public function destroy(TaxPeriod $taxPeriod)
    {
        $taxPeriod->delete();

        return response()->json(['message' => 'Tax period deleted successfully.']);
    }
}
