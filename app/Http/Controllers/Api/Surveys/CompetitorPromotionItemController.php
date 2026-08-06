<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPromotionItem;
use Illuminate\Http\Request;

class CompetitorPromotionItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPromotionItem::with($with);

        if ($request->competitor_promotion_id) {
            $query->where('competitor_promotion_id', $request->competitor_promotion_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('offer_type', 'like', "%$s%");
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
            'competitor_promotion_id' => 'required|exists:competitor_promotions,id',
            'competitor_product_id' => 'required|exists:competitor_products,id',
            'offer_type' => 'required|in:DISCOUNT_PERCENT,DISCOUNT_AMOUNT,FREE_GOODS,BUNDLE,CASHBACK',
            'offer_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        return response()->json(CompetitorPromotionItem::create($data), 201);
    }

    public function show(CompetitorPromotionItem $competitorPromotionItem)
    {
        return $competitorPromotionItem->load(['promotion', 'competitorProduct']);
    }

    public function update(Request $request, CompetitorPromotionItem $competitorPromotionItem)
    {
        $data = $request->validate([
            'competitor_promotion_id' => 'sometimes|required|exists:competitor_promotions,id',
            'competitor_product_id' => 'sometimes|required|exists:competitor_products,id',
            'offer_type' => 'sometimes|required|in:DISCOUNT_PERCENT,DISCOUNT_AMOUNT,FREE_GOODS,BUNDLE,CASHBACK',
            'offer_value' => 'sometimes|required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $competitorPromotionItem->update($data);
        return response()->json($competitorPromotionItem);
    }

    public function destroy(CompetitorPromotionItem $competitorPromotionItem)
    {
        $competitorPromotionItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorPromotionItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorPromotionItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
