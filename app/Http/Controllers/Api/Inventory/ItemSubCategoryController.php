<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemSubCategory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemSubCategoryController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemSubCategory::with($with);

        if ($request->company_id) {
            $query->whereHas('itemCategory', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->item_category_id) {
            $query->where('item_category_id', $request->item_category_id);
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
        $data = $request->validate(ValidationRules::for('item_sub_category', 'store'));

        if (empty($data['company_id'])) {
            $data['company_id'] = $request->header('X-Company-Id')
                ?? auth()->user()->company_id
                ?? null;
        }

        return response()->json(ItemSubCategory::create($data), 201);
    }

    public function show($id)
    {
        $model = ItemSubCategory::withoutTrashed()->findOrFail($id);
        return response()->json($model->load(['itemCategory']));
    }

    public function update(Request $request, $id)
    {
        $model = ItemSubCategory::withoutTrashed()->findOrFail($id);
        $data = $request->validate(ValidationRules::for('item_sub_category', 'update', $model));

        $model->update($data);
        $model->refresh();

        return response()->json($model);
    }

    public function destroy($id)
    {
        $model = ItemSubCategory::withoutTrashed()->findOrFail($id);
        $model->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ItemSubCategory::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ItemSubCategory::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $query = ItemSubCategory::withTrashed()
            ->where('code', 'like', 'SC-%');

        if ($request->company_id) {
            $query->whereHas('itemCategory', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        $last = $query->get()
            ->filter(fn($item) => preg_match('/^SC-\d{5}$/', $item->code))
            ->map(fn($item) => (int) preg_replace('/\D/', '', $item->code))
            ->filter(fn($num) => $num > 0)
            ->max();

        $next = ($last ?? 0) + 1;

        return response()->json(['code' => 'SC-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
    }

    public function schema()
    {
        return ValidationRules::for('item_sub_category', 'store');
    }
}
