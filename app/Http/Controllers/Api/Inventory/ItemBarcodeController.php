<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemBarcode;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemBarcodeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemBarcode::with($with);

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
        $data = $request->validate(ValidationRules::for('item_barcode', 'store'));

        return response()->json(ItemBarcode::create($data), 201);
    }

    public function show(ItemBarcode $item_barcode)
    {
        return $item_barcode->load(['item', 'unit']);
    }

    public function update(Request $request, ItemBarcode $item_barcode)
    {
        $data = $request->validate(ValidationRules::for('item_barcode', 'update', $item_barcode));

        $item_barcode->update($data);

        return response()->json($item_barcode);
    }

    public function destroy(ItemBarcode $item_barcode)
    {
        $item_barcode->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ItemBarcode::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ItemBarcode::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('item_barcode', 'store');
    }
}
