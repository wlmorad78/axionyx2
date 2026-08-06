<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxReturnDetail;
use Illuminate\Http\Request;

class TaxReturnDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxReturnDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('tax_return_id', $search);
        }

        $taxReturnDetails = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($taxReturnDetails);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tax_return_id' => 'required',
            'tax_type_id' => 'required',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $taxReturnDetail = TaxReturnDetail::create($validated);

        return response()->json($taxReturnDetail, 201);
    }

    public function show(TaxReturnDetail $taxReturnDetail)
    {
        return response()->json($taxReturnDetail);
    }

    public function update(Request $request, TaxReturnDetail $taxReturnDetail)
    {
        $validated = $request->validate([
            'tax_return_id' => 'required',
            'tax_type_id' => 'required',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $taxReturnDetail->update($validated);

        return response()->json($taxReturnDetail);
    }

    public function destroy(TaxReturnDetail $taxReturnDetail)
    {
        $taxReturnDetail->delete();

        return response()->json(['message' => 'Tax return detail deleted successfully.']);
    }
}
