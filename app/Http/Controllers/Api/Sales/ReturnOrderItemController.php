<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\ReturnOrderItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ReturnOrderItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ReturnOrderItem::with($with);
        if ($request->return_order_id) $query->where('return_order_id', $request->return_order_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('return_order_item', 'store'));
        return response()->json(ReturnOrderItem::create($data), 201);
    }

    public function show(ReturnOrderItem $returnOrderItem)
    {
        return $returnOrderItem->load(['returnOrder', 'item', 'unit']);
    }

    public function update(Request $request, ReturnOrderItem $returnOrderItem)
    {
        $data = $request->validate(ValidationRules::for('return_order_item', 'update', $returnOrderItem));
        $returnOrderItem->update($data);
        return response()->json($returnOrderItem);
    }

    public function destroy(ReturnOrderItem $returnOrderItem)
    {
        $returnOrderItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = ReturnOrderItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        ReturnOrderItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('return_order_item', 'store');
    }
}
