<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxGroupDetail;
use Illuminate\Http\Request;

class TaxGroupDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxGroupDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_group_id', 'like', "%$s%");
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
            'tax_group_id' => 'required',
            'tax_type_id' => 'required',
            'calculation_order' => 'integer',
        ]);

        return response()->json(TaxGroupDetail::create($data), 201);
    }

    public function show(TaxGroupDetail $taxGroupDetail)
    {
        return $taxGroupDetail;
    }

    public function update(Request $request, TaxGroupDetail $taxGroupDetail)
    {
        $data = $request->validate([
            'tax_group_id' => 'required',
            'tax_type_id' => 'required',
            'calculation_order' => 'integer',
        ]);

        $taxGroupDetail->update($data);

        return response()->json($taxGroupDetail);
    }

    public function destroy(TaxGroupDetail $taxGroupDetail)
    {
        $taxGroupDetail->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $taxGroupDetail = TaxGroupDetail::onlyTrashed()->findOrFail($id);
        $taxGroupDetail->restore();

        return response()->json($taxGroupDetail);
    }

    public function forceDelete(int $id)
    {
        TaxGroupDetail::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
