<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoiceItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceItem::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_item', 'store'));
        return response()->json(SalesInvoiceItem::create($data), 201);
    }

    public function show(SalesInvoiceItem $salesInvoiceItem)
    {
        return $salesInvoiceItem->load(['item', 'unit', 'warehouse']);
    }

    public function update(Request $request, SalesInvoiceItem $salesInvoiceItem)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_item', 'update', $salesInvoiceItem));
        $salesInvoiceItem->update($data);
        return response()->json($salesInvoiceItem);
    }

    public function destroy(SalesInvoiceItem $salesInvoiceItem)
    {
        $salesInvoiceItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SalesInvoiceItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SalesInvoiceItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_invoice_item', 'store');
    }
}
