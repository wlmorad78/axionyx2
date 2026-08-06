<?php

namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Suppliers\SupplierGroup;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierGroupController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierGroup::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_group', 'store'));
        return response()->json(SupplierGroup::create($data), 201);
    }

    public function show(SupplierGroup $supplierGroup)
    {
        return $supplierGroup->load(['company', 'suppliers']);
    }

    public function update(Request $request, SupplierGroup $supplierGroup)
    {
        $data = $request->validate(ValidationRules::for('supplier_group', 'update', $supplierGroup));
        $supplierGroup->update($data);
        return response()->json($supplierGroup);
    }

    public function destroy(SupplierGroup $supplierGroup)
    {
        $supplierGroup->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SupplierGroup::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SupplierGroup::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('supplier_group', 'store');
    }
}
