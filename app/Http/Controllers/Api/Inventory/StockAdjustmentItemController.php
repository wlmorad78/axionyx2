<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockAdjustmentItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StockAdjustmentItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = StockAdjustmentItem::with($with);
        if ($request->stock_adjustment_id) $query->where('stock_adjustment_id', $request->stock_adjustment_id);
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
        $data = $request->validate(ValidationRules::for('stock_adjustment_item', 'store'));
        return response()->json(StockAdjustmentItem::create($data), 201);
    }

    public function show(StockAdjustmentItem $stockAdjustmentItem)
    {
        return $stockAdjustmentItem->load([
            'stockAdjustment', 'item', 'unit', 'batch',
        ]);
    }

    public function update(Request $request, StockAdjustmentItem $stockAdjustmentItem)
    {
        $data = $request->validate(ValidationRules::for('stock_adjustment_item', 'update', $stockAdjustmentItem));
        $stockAdjustmentItem->update($data);
        return response()->json($stockAdjustmentItem);
    }

    public function destroy(StockAdjustmentItem $stockAdjustmentItem)
    {
        $stockAdjustmentItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = StockAdjustmentItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        StockAdjustmentItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('stock_adjustment_item', 'store');
    }
}
