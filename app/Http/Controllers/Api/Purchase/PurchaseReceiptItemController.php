<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceiptItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReceiptItemController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseReceiptItem::with(['item', 'unit']);

        if ($request->filled('purchase_receipt_id')) {
            $query->where('purchase_receipt_id', $request->purchase_receipt_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt_item', 'store'));
        $item = PurchaseReceiptItem::create($validated);

        return response()->json($item, 201);
    }

    public function show(PurchaseReceiptItem $purchaseReceiptItem)
    {
        $purchaseReceiptItem->load(['item', 'unit', 'purchaseReceipt']);

        return response()->json($purchaseReceiptItem);
    }

    public function update(Request $request, PurchaseReceiptItem $purchaseReceiptItem)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt_item', 'update', $purchaseReceiptItem));
        $purchaseReceiptItem->update($validated);

        return response()->json($purchaseReceiptItem);
    }

    public function destroy(PurchaseReceiptItem $purchaseReceiptItem)
    {
        $purchaseReceiptItem->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = PurchaseReceiptItem::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        PurchaseReceiptItem::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_receipt_item', 'store');
    }
}
