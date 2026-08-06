<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\SupplierQuotationItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierQuotationItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierQuotationItem::with($with);
        if ($request->supplier_quotation_id) $query->where('supplier_quotation_id', $request->supplier_quotation_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation_item', 'store'));
        $item = SupplierQuotationItem::create($data);
        return response()->json($item, 201);
    }

    public function show(SupplierQuotationItem $supplierQuotationItem)
    {
        return $supplierQuotationItem->load(['supplierQuotation', 'item', 'unit']);
    }

    public function update(Request $request, SupplierQuotationItem $supplierQuotationItem)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation_item', 'update', $supplierQuotationItem));
        $supplierQuotationItem->update($data);
        return response()->json($supplierQuotationItem);
    }

    public function destroy(SupplierQuotationItem $supplierQuotationItem)
    {
        $supplierQuotationItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SupplierQuotationItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SupplierQuotationItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('supplier_quotation_item', 'store');
    }
}
