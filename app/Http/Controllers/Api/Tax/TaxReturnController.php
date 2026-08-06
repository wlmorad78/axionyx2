<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxReturn;
use Illuminate\Http\Request;

class TaxReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxReturn::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $taxReturns = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($taxReturns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'return_no' => 'required',
            'tax_period_id' => 'required',
            'total_sales' => 'numeric',
            'total_purchases' => 'numeric',
            'output_tax' => 'numeric',
            'input_tax' => 'numeric',
            'net_tax' => 'numeric',
            'status' => 'required|in:DRAFT,SUBMITTED,APPROVED',
        ]);

        $taxReturn = TaxReturn::create($validated);

        return response()->json($taxReturn, 201);
    }

    public function show(TaxReturn $taxReturn)
    {
        $taxReturn->load('details');

        return response()->json($taxReturn);
    }

    public function update(Request $request, TaxReturn $taxReturn)
    {
        $validated = $request->validate([
            'return_no' => 'required',
            'tax_period_id' => 'required',
            'total_sales' => 'numeric',
            'total_purchases' => 'numeric',
            'output_tax' => 'numeric',
            'input_tax' => 'numeric',
            'net_tax' => 'numeric',
            'status' => 'required|in:DRAFT,SUBMITTED,APPROVED',
        ]);

        $taxReturn->update($validated);

        return response()->json($taxReturn);
    }

    public function destroy(TaxReturn $taxReturn)
    {
        $taxReturn->delete();

        return response()->json(['message' => 'Tax return deleted successfully.']);
    }
}
