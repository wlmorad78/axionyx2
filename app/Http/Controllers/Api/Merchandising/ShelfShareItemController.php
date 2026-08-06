<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfShareItem;
use Illuminate\Http\Request;

class ShelfShareItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ShelfShareItem::with($with);

        if ($request->shelf_share_survey_id) {
            $query->where('shelf_share_survey_id', $request->shelf_share_survey_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('brand_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shelf_share_survey_id' => 'required|exists:shelf_share_surveys,id',
            'brand_name' => 'required|string|max:255',
            'facings_count' => 'required|integer|min:0',
            'shelf_percentage' => 'required|numeric|min:0|max:100',
        ]);

        return response()->json(ShelfShareItem::create($data), 201);
    }

    public function show(ShelfShareItem $shelfShareItem)
    {
        return $shelfShareItem->load(['survey']);
    }

    public function update(Request $request, ShelfShareItem $shelfShareItem)
    {
        $data = $request->validate([
            'shelf_share_survey_id' => 'sometimes|required|exists:shelf_share_surveys,id',
            'brand_name' => 'sometimes|required|string|max:255',
            'facings_count' => 'sometimes|required|integer|min:0',
            'shelf_percentage' => 'sometimes|required|numeric|min:0|max:100',
        ]);

        $shelfShareItem->update($data);
        return response()->json($shelfShareItem);
    }

    public function destroy(ShelfShareItem $shelfShareItem)
    {
        $shelfShareItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ShelfShareItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ShelfShareItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
