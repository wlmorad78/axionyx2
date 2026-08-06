<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxRate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_type_id', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tax_type_id' => 'required|exists:tax_rates,id',
            'rate_percent' => 'required|numeric',
            'effective_from' => 'required|date',
        ]);

        return response()->json(TaxRate::create($data), 201);
    }

    public function show(TaxRate $taxRate)
    {
        return $taxRate;
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $data = $request->validate([
            'tax_type_id' => 'required|exists:tax_rates,id',
            'rate_percent' => 'required|numeric',
            'effective_from' => 'required|date',
        ]);

        $taxRate->update($data);

        return response()->json($taxRate);
    }

    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $taxRate = TaxRate::onlyTrashed()->findOrFail($id);
        $taxRate->restore();

        return response()->json($taxRate);
    }

    public function forceDelete(int $id)
    {
        TaxRate::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
