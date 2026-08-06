<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryRevaluationItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryRevaluationItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryRevaluationItem::with($with);
        if ($request->inventory_revaluation_id) $query->where('inventory_revaluation_id', $request->inventory_revaluation_id);
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
        $data = $request->validate(ValidationRules::for('inventory_revaluation_item', 'store'));
        return response()->json(InventoryRevaluationItem::create($data), 201);
    }

    public function show(InventoryRevaluationItem $inventoryRevaluationItem)
    {
        return $inventoryRevaluationItem->load([
            'inventoryRevaluation', 'item', 'unit', 'batch',
        ]);
    }

    public function update(Request $request, InventoryRevaluationItem $inventoryRevaluationItem)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation_item', 'update', $inventoryRevaluationItem));
        $inventoryRevaluationItem->update($data);
        return response()->json($inventoryRevaluationItem);
    }

    public function destroy(InventoryRevaluationItem $inventoryRevaluationItem)
    {
        $inventoryRevaluationItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = InventoryRevaluationItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        InventoryRevaluationItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('inventory_revaluation_item', 'store');
    }
}
