<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseInvoiceItemController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseInvoiceItem::with(['item', 'unit']);

        if ($request->filled('purchase_invoice_id')) {
            $query->where('purchase_invoice_id', $request->purchase_invoice_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_invoice_item', 'store'));
        $item = PurchaseInvoiceItem::create($validated);

        return response()->json($item, 201);
    }

    public function show(PurchaseInvoiceItem $purchaseInvoiceItem)
    {
        $purchaseInvoiceItem->load(['item', 'unit', 'purchaseInvoice']);

        return response()->json($purchaseInvoiceItem);
    }

    public function update(Request $request, PurchaseInvoiceItem $purchaseInvoiceItem)
    {
        $validated = $request->validate(ValidationRules::for('purchase_invoice_item', 'update', $purchaseInvoiceItem));
        $purchaseInvoiceItem->update($validated);

        return response()->json($purchaseInvoiceItem);
    }

    public function destroy(PurchaseInvoiceItem $purchaseInvoiceItem)
    {
        $purchaseInvoiceItem->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = PurchaseInvoiceItem::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        PurchaseInvoiceItem::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_invoice_item', 'store');
    }
}
