<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransactionItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryTransactionItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryTransactionItem::with($with);
        if ($request->inventory_transaction_id) $query->where('inventory_transaction_id', $request->inventory_transaction_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%")->orWhere('batch_no', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_item', 'store'));
        return response()->json(InventoryTransactionItem::create($data), 201);
    }

    public function show(InventoryTransactionItem $inventoryTransactionItem)
    {
        return $inventoryTransactionItem->load([
            'inventoryTransaction', 'item', 'unit', 'batch',
        ]);
    }

    public function update(Request $request, InventoryTransactionItem $inventoryTransactionItem)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_item', 'update', $inventoryTransactionItem));
        $inventoryTransactionItem->update($data);
        return response()->json($inventoryTransactionItem);
    }

    public function destroy(InventoryTransactionItem $inventoryTransactionItem)
    {
        $inventoryTransactionItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = InventoryTransactionItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        InventoryTransactionItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('inventory_transaction_item', 'store');
    }
}
