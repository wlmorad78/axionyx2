<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequestItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseRequestItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PurchaseRequestItem::with($with);
        if ($request->purchase_request_id) $query->where('purchase_request_id', $request->purchase_request_id);
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
        $data = $request->validate(ValidationRules::for('purchase_request_item', 'store'));
        $item = PurchaseRequestItem::create($data);
        return response()->json($item, 201);
    }

    public function show(PurchaseRequestItem $purchaseRequestItem)
    {
        return $purchaseRequestItem->load(['purchaseRequest', 'item', 'unit']);
    }

    public function update(Request $request, PurchaseRequestItem $purchaseRequestItem)
    {
        $data = $request->validate(ValidationRules::for('purchase_request_item', 'update', $purchaseRequestItem));
        $purchaseRequestItem->update($data);
        return response()->json($purchaseRequestItem);
    }

    public function destroy(PurchaseRequestItem $purchaseRequestItem)
    {
        $purchaseRequestItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = PurchaseRequestItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        PurchaseRequestItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_request_item', 'store');
    }
}
