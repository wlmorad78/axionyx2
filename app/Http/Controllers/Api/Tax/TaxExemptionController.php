<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxExemption;
use Illuminate\Http\Request;

class TaxExemptionController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxExemption::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('exemption_code', 'like', "%{$search}%")
                  ->orWhere('exemption_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $taxExemptions = $query->paginate($perPage);

        return response()->json($taxExemptions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exemption_code' => 'required',
            'exemption_name' => 'required',
            'effective_from' => 'required|date',
        ]);

        $taxExemption = TaxExemption::create($validated);

        return response()->json($taxExemption, 201);
    }

    public function show(TaxExemption $taxExemption)
    {
        return response()->json($taxExemption);
    }

    public function update(Request $request, TaxExemption $taxExemption)
    {
        $validated = $request->validate([
            'exemption_code' => 'sometimes|required',
            'exemption_name' => 'sometimes|required',
            'effective_from' => 'sometimes|required|date',
        ]);

        $taxExemption->update($validated);

        return response()->json($taxExemption);
    }

    public function destroy(TaxExemption $taxExemption)
    {
        $taxExemption->delete();

        return response()->json(null, 204);
    }
}
