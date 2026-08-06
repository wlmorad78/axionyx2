<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Item;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = Item::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)->with($with)
            ->select('items.*')
            ->selectRaw('COALESCE((
                SELECT SUM(iti.qty)
                FROM inventory_transaction_items iti
                JOIN inventory_transactions it ON it.id = iti.inventory_transaction_id
                WHERE iti.item_id = items.id
                AND it.status = ?
                AND it.deleted_at IS NULL
            ), 0) + COALESCE((
                SELECT SUM(iob.qty)
                FROM inventory_opening_balances iob
                WHERE iob.item_id = items.id
            ), 0) as stock_qty', ['posted']);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->branch_id) {
            $query->where(function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        if ($request->product_company_id) {
            $query->where('product_company_id', $request->product_company_id);
        }

        if ($request->item_category_id) {
            $query->where('item_category_id', $request->item_category_id);
        }

        if ($request->item_sub_category_id) {
            $query->where('item_sub_category_id', $request->item_sub_category_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%")
                    ->orWhere('barcode', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item', 'store'));

        return response()->json(Item::create($data), 201);
    }

    public function show(Request $request, $id)
    {
        $model = Item::withoutTrashed()->findOrFail($id);
        $with = $request->with ? explode(',', $request->with) : [
            'company',
            'branch',
            'productCompany',
            'itemCategory',
            'itemSubCategory',
            'baseUnit',
        ];
        return response()->json($model->load($with));
    }

    public function update(Request $request, $id)
    {
        $model = Item::withoutTrashed()->findOrFail($id);
        $data = $request->validate(ValidationRules::for('item', 'update', $model));

        $model->update($data);
        $model->refresh();

        return response()->json($model);
    }

    public function destroy($id)
    {
        $model = Item::withoutTrashed()->findOrFail($id);
        $model->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = Item::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        Item::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $maxNum = Item::withTrashed()
            ->where('code', 'like', 'ITM-%')
            ->get()
            ->map(fn($item) => (int) preg_replace('/\D/', '', $item->code))
            ->filter(fn($num) => $num > 0)
            ->max();

        $next = ($maxNum && $maxNum > 0) ? $maxNum + 1 : 1;

        return response()->json(['code' => 'ITM-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
    }

    public function schema()
    {
        return ValidationRules::for('item', 'store');
    }
}
