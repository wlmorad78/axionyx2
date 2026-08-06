<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('rule_name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $taxRules = $query->paginate($perPage);

        return response()->json($taxRules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rule_name' => 'required',
            'tax_group_id' => 'required',
            'effective_from' => 'required|date',
            'priority' => 'integer',
        ]);

        $taxRule = TaxRule::create($validated);

        return response()->json($taxRule, 201);
    }

    public function show(TaxRule $taxRule): JsonResponse
    {
        return response()->json($taxRule);
    }

    public function update(Request $request, TaxRule $taxRule): JsonResponse
    {
        $validated = $request->validate([
            'rule_name' => 'required',
            'tax_group_id' => 'required',
            'effective_from' => 'required|date',
            'priority' => 'integer',
        ]);

        $taxRule->update($validated);

        return response()->json($taxRule);
    }

    public function destroy(TaxRule $taxRule): JsonResponse
    {
        $taxRule->delete();

        return response()->json(['message' => 'Tax rule deleted successfully']);
    }
}
