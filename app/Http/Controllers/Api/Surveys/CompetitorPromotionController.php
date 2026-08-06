<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPromotion;
use Illuminate\Http\Request;

class CompetitorPromotionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPromotion::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('promotion_name', 'like', "%$s%")
                    ->orWhere('status', 'like', "%$s%");
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
            'competitor_id' => 'required|exists:competitors,id',
            'promotion_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,EXPIRED,CANCELLED',
        ]);

        return response()->json(CompetitorPromotion::create($data), 201);
    }

    public function show(CompetitorPromotion $competitorPromotion)
    {
        return $competitorPromotion->load(['competitor', 'items']);
    }

    public function update(Request $request, CompetitorPromotion $competitorPromotion)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'promotion_name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:ACTIVE,EXPIRED,CANCELLED',
        ]);

        $competitorPromotion->update($data);
        return response()->json($competitorPromotion);
    }

    public function destroy(CompetitorPromotion $competitorPromotion)
    {
        $competitorPromotion->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorPromotion::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorPromotion::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
