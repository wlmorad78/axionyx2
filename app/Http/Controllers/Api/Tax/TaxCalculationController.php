<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxCalculation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxCalculationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxCalculation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_type', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $taxCalculations = $query->paginate($perPage);

        return response()->json($taxCalculations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_type' => 'required|in:SALES_INVOICE,PURCHASE_INVOICE,RETURN,CREDIT_NOTE,DEBIT_NOTE',
            'reference_id' => 'required',
            'calculation_date' => 'required|date',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
            'total_amount' => 'numeric',
        ]);

        $taxCalculation = TaxCalculation::create($validated);

        return response()->json($taxCalculation, 201);
    }

    public function show(TaxCalculation $taxCalculation): JsonResponse
    {
        $taxCalculation->load('details');

        return response()->json($taxCalculation);
    }

    public function update(Request $request, TaxCalculation $taxCalculation): JsonResponse
    {
        $validated = $request->validate([
            'reference_type' => 'required|in:SALES_INVOICE,PURCHASE_INVOICE,RETURN,CREDIT_NOTE,DEBIT_NOTE',
            'reference_id' => 'required',
            'calculation_date' => 'required|date',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
            'total_amount' => 'numeric',
        ]);

        $taxCalculation->update($validated);

        return response()->json($taxCalculation);
    }

    public function destroy(TaxCalculation $taxCalculation): JsonResponse
    {
        $taxCalculation->delete();

        return response()->json(['message' => 'Tax calculation deleted successfully']);
    }
}
