<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseTransferItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseTransferItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = WarehouseTransferItem::with($with);
        if ($request->warehouse_transfer_id) $query->where('warehouse_transfer_id', $request->warehouse_transfer_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
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
        $data = $request->validate(ValidationRules::for('warehouse_transfer_item', 'store'));
        return response()->json(WarehouseTransferItem::create($data), 201);
    }

    public function show(WarehouseTransferItem $warehouseTransferItem)
    {
        return $warehouseTransferItem->load([
            'warehouseTransfer', 'item', 'unit', 'batch',
        ]);
    }

    public function update(Request $request, WarehouseTransferItem $warehouseTransferItem)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer_item', 'update', $warehouseTransferItem));
        $warehouseTransferItem->update($data);
        return response()->json($warehouseTransferItem);
    }

    public function destroy(WarehouseTransferItem $warehouseTransferItem)
    {
        $warehouseTransferItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = WarehouseTransferItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        WarehouseTransferItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('warehouse_transfer_item', 'store');
    }
}
