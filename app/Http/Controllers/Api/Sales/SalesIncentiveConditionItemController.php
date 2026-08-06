<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentiveConditionItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveConditionItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentiveConditionItem::with($with);
        if ($request->condition_id) $query->where('condition_id', $request->condition_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition_item', 'store'));
        return response()->json(SalesIncentiveConditionItem::create($data), 201);
    }

    public function show(SalesIncentiveConditionItem $item)
    {
        return $item->load(['condition', 'item']);
    }

    public function update(Request $request, SalesIncentiveConditionItem $item)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition_item', 'update', $item));
        $item->update($data);
        return response()->json($item);
    }

    public function destroy(SalesIncentiveConditionItem $item)
    {
        $item->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SalesIncentiveConditionItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SalesIncentiveConditionItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_incentive_condition_item', 'store');
    }
}
