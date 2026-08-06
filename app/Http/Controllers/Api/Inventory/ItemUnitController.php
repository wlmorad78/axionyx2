<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ItemUnit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemUnitController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemUnit::with($with);

        if ($request->item_id) {
            $query->where('item_id', $request->item_id);
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
        $data = $request->validate(ValidationRules::for('item_unit', 'store'));

        return response()->json(ItemUnit::create($data), 201);
    }

    public function show(ItemUnit $item_unit)
    {
        return $item_unit->load(['item', 'unit']);
    }

    public function update(Request $request, ItemUnit $item_unit)
    {
        $data = $request->validate(ValidationRules::for('item_unit', 'update', $item_unit));

        $item_unit->update($data);

        return response()->json($item_unit);
    }

    public function destroy(ItemUnit $item_unit)
    {
        $item_unit->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ItemUnit::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ItemUnit::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('item_unit', 'store');
    }
}
