<?php

namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Suppliers\SupplierContact;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierContactController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierContact::with($with);

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('contact_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_contact', 'store'));
        return response()->json(SupplierContact::create($data), 201);
    }

    public function show(SupplierContact $supplierContact)
    {
        return $supplierContact->load(['supplier']);
    }

    public function update(Request $request, SupplierContact $supplierContact)
    {
        $data = $request->validate(ValidationRules::for('supplier_contact', 'update', $supplierContact));
        $supplierContact->update($data);
        return response()->json($supplierContact);
    }

    public function destroy(SupplierContact $supplierContact)
    {
        $supplierContact->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SupplierContact::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SupplierContact::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('supplier_contact', 'store');
    }
}
