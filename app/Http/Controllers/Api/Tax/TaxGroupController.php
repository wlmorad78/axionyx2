<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\TaxGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxGroup::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('group_code', 'like', "%$s%")
                    ->orWhere('group_name', 'like', "%$s%");
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
            'group_code' => 'required',
            'group_name' => 'required',
        ]);

        $data['company_id'] = Auth::user()->company_id;

        return response()->json(TaxGroup::create($data), 201);
    }

    public function show(TaxGroup $taxGroup)
    {
        return $taxGroup;
    }

    public function update(Request $request, TaxGroup $taxGroup)
    {
        $data = $request->validate([
            'group_code' => 'required',
            'group_name' => 'required',
        ]);

        $taxGroup->update($data);

        return response()->json($taxGroup);
    }

    public function destroy(TaxGroup $taxGroup)
    {
        $taxGroup->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $taxGroup = TaxGroup::onlyTrashed()->findOrFail($id);
        $taxGroup->restore();

        return response()->json($taxGroup);
    }

    public function forceDelete(int $id)
    {
        TaxGroup::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
