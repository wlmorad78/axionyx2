<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ItemBatch;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemBatchController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ItemBatch::with($with);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('batch_no', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_batch', 'store'));
        return response()->json(ItemBatch::create($data), 201);
    }

    public function show(ItemBatch $itemBatch)
    {
        return $itemBatch->load([
            'company', 'item', 'warehouse',
        ]);
    }

    public function update(Request $request, ItemBatch $itemBatch)
    {
        $data = $request->validate(ValidationRules::for('item_batch', 'update', $itemBatch));
        $itemBatch->update($data);
        return response()->json($itemBatch);
    }

    public function destroy(ItemBatch $itemBatch)
    {
        $itemBatch->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = ItemBatch::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        ItemBatch::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('item_batch', 'store');
    }
}
