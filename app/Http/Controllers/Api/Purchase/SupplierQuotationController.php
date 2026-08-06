<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\SupplierQuotation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierQuotationController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierQuotation::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('quotation_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation', 'store'));
        return response()->json(SupplierQuotation::create($data), 201);
    }

    public function show(SupplierQuotation $supplierQuotation)
    {
        return $supplierQuotation->load([
            'company', 'branch', 'supplier', 'createdByEmployee',
            'items.item', 'items.unit',
        ]);
    }

    public function update(Request $request, SupplierQuotation $supplierQuotation)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation', 'update', $supplierQuotation));
        $supplierQuotation->update($data);
        return response()->json($supplierQuotation);
    }

    public function destroy(SupplierQuotation $supplierQuotation)
    {
        $supplierQuotation->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SupplierQuotation::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SupplierQuotation::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('supplier_quotation', 'store');
    }
}
