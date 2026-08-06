<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPhoto;
use Illuminate\Http\Request;

class CompetitorPhotoController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPhoto::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('photo_type', 'like', "%$s%");
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
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'competitor_id' => 'nullable|exists:competitors,id',
            'photo_type' => 'required|in:PRICE_TAG,SHELF,DISPLAY,PROMOTION,NEW_PRODUCT',
            'file_path' => 'required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        return response()->json(CompetitorPhoto::create($data), 201);
    }

    public function show(CompetitorPhoto $competitorPhoto)
    {
        return $competitorPhoto->load(['customer', 'salesRep', 'competitor']);
    }

    public function update(Request $request, CompetitorPhoto $competitorPhoto)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'competitor_id' => 'nullable|exists:competitors,id',
            'photo_type' => 'sometimes|required|in:PRICE_TAG,SHELF,DISPLAY,PROMOTION,NEW_PRODUCT',
            'file_path' => 'sometimes|required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $competitorPhoto->update($data);
        return response()->json($competitorPhoto);
    }

    public function destroy(CompetitorPhoto $competitorPhoto)
    {
        $competitorPhoto->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorPhoto::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorPhoto::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
