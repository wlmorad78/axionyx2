<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxCalculationDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxCalculationDetailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxCalculationDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('tax_calculation_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $details = $query->paginate($perPage);

        return response()->json($details);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_calculation_id' => 'required',
            'tax_type_id' => 'required',
            'tax_rate' => 'numeric',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $detail = TaxCalculationDetail::create($validated);

        return response()->json($detail, 201);
    }

    public function show(TaxCalculationDetail $taxCalculationDetail): JsonResponse
    {
        return response()->json($taxCalculationDetail);
    }

    public function update(Request $request, TaxCalculationDetail $taxCalculationDetail): JsonResponse
    {
        $validated = $request->validate([
            'tax_calculation_id' => 'required',
            'tax_type_id' => 'required',
            'tax_rate' => 'numeric',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $taxCalculationDetail->update($validated);

        return response()->json($taxCalculationDetail);
    }

    public function destroy(TaxCalculationDetail $taxCalculationDetail): JsonResponse
    {
        $taxCalculationDetail->delete();

        return response()->json(['message' => 'Tax calculation detail deleted successfully']);
    }
}
