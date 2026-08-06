<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockCountItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StockCountItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = StockCountItem::with($with);
        if ($request->stock_count_id) $query->where('stock_count_id', $request->stock_count_id);
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
        $data = $request->validate(ValidationRules::for('stock_count_item', 'store'));
        return response()->json(StockCountItem::create($data), 201);
    }

    public function show(StockCountItem $stockCountItem)
    {
        return $stockCountItem->load([
            'stockCount', 'item', 'unit', 'batch',
        ]);
    }

    public function update(Request $request, StockCountItem $stockCountItem)
    {
        $data = $request->validate(ValidationRules::for('stock_count_item', 'update', $stockCountItem));
        $stockCountItem->update($data);
        return response()->json($stockCountItem);
    }

    public function destroy(StockCountItem $stockCountItem)
    {
        $stockCountItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = StockCountItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        StockCountItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('stock_count_item', 'store');
    }
}
