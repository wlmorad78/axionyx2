<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Pricing\ItemPrice;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemPriceController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemPrice::with($with);

        if ($request->item_id) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->price_list_id) {
            $query->where('price_list_id', $request->price_list_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_price', 'store'));

        return response()->json(ItemPrice::create($data), 201);
    }

    public function show(ItemPrice $item_price)
    {
        return $item_price->load(['item', 'priceList', 'unit']);
    }

    public function update(Request $request, ItemPrice $item_price)
    {
        $data = $request->validate(ValidationRules::for('item_price', 'update', $item_price));

        $item_price->update($data);

        return response()->json($item_price);
    }

    public function destroy(ItemPrice $item_price)
    {
        $item_price->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ItemPrice::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ItemPrice::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('item_price', 'store');
    }
}
