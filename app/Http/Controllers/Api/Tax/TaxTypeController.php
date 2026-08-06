<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\TaxType;
use Illuminate\Http\Request;

class TaxTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_code', 'like', "%$s%")
                    ->orWhere('tax_name', 'like', "%$s%")
                    ->orWhere('tax_category', 'like', "%$s%");
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
            'tax_code' => 'required',
            'tax_name' => 'required',
            'tax_category' => 'required|in:VAT,WITHHOLDING,EXCISE,STAMP,OTHER',
        ]);

        return response()->json(TaxType::create($data), 201);
    }

    public function show(TaxType $taxType)
    {
        return $taxType;
    }

    public function update(Request $request, TaxType $taxType)
    {
        $data = $request->validate([
            'tax_code' => 'required',
            'tax_name' => 'required',
            'tax_category' => 'required|in:VAT,WITHHOLDING,EXCISE,STAMP,OTHER',
        ]);

        $taxType->update($data);

        return response()->json($taxType);
    }

    public function destroy(TaxType $taxType)
    {
        $taxType->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $taxType = TaxType::onlyTrashed()->findOrFail($id);
        $taxType->restore();

        return response()->json($taxType);
    }

    public function forceDelete(int $id)
    {
        TaxType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
