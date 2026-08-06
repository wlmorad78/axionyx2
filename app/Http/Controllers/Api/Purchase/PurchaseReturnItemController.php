<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturnItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReturnItemController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseReturnItem::with(['item', 'unit']);

        if ($request->filled('purchase_return_id')) {
            $query->where('purchase_return_id', $request->purchase_return_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_return_item', 'store'));
        $item = PurchaseReturnItem::create($validated);

        return response()->json($item, 201);
    }

    public function show(PurchaseReturnItem $purchaseReturnItem)
    {
        $purchaseReturnItem->load(['item', 'unit', 'purchaseReturn']);

        return response()->json($purchaseReturnItem);
    }

    public function update(Request $request, PurchaseReturnItem $purchaseReturnItem)
    {
        $validated = $request->validate(ValidationRules::for('purchase_return_item', 'update', $purchaseReturnItem));
        $purchaseReturnItem->update($validated);

        return response()->json($purchaseReturnItem);
    }

    public function destroy(PurchaseReturnItem $purchaseReturnItem)
    {
        $purchaseReturnItem->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = PurchaseReturnItem::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        PurchaseReturnItem::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_return_item', 'store');
    }
}
