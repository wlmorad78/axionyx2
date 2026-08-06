<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxJurisdictionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxJurisdiction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('jurisdiction_code', 'like', "%{$search}%")
                  ->orWhere('jurisdiction_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $jurisdictions = $query->paginate($perPage);

        return response()->json($jurisdictions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jurisdiction_code' => 'required',
            'jurisdiction_name' => 'required',
            'country_id' => 'required',
        ]);

        $jurisdiction = TaxJurisdiction::create($validated);

        return response()->json($jurisdiction, 201);
    }

    public function show(TaxJurisdiction $taxJurisdiction): JsonResponse
    {
        return response()->json($taxJurisdiction);
    }

    public function update(Request $request, TaxJurisdiction $taxJurisdiction): JsonResponse
    {
        $validated = $request->validate([
            'jurisdiction_code' => 'required',
            'jurisdiction_name' => 'required',
            'country_id' => 'required',
        ]);

        $taxJurisdiction->update($validated);

        return response()->json($taxJurisdiction);
    }

    public function destroy(TaxJurisdiction $taxJurisdiction): JsonResponse
    {
        $taxJurisdiction->delete();

        return response()->json(['message' => 'Tax jurisdiction deleted successfully']);
    }
}
