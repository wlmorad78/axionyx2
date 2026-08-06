<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\IssueOrderItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IssueOrderItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = IssueOrderItem::with($with);
        if ($request->issue_order_id) $query->where('issue_order_id', $request->issue_order_id);
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
        $data = $request->validate(ValidationRules::for('issue_order_item', 'store'));
        return response()->json(IssueOrderItem::create($data), 201);
    }

    public function show(IssueOrderItem $issueOrderItem)
    {
        return $issueOrderItem->load(['issueOrder', 'item', 'unit']);
    }

    public function update(Request $request, IssueOrderItem $issueOrderItem)
    {
        $data = $request->validate(ValidationRules::for('issue_order_item', 'update', $issueOrderItem));
        $issueOrderItem->update($data);
        return response()->json($issueOrderItem);
    }

    public function destroy(IssueOrderItem $issueOrderItem)
    {
        $issueOrderItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = IssueOrderItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        IssueOrderItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('issue_order_item', 'store');
    }
}