<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorProduct;
use Illuminate\Http\Request;

class CompetitorProductController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorProduct::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('product_code', 'like', "%$s%")
                    ->orWhere('product_name', 'like', "%$s%");
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
            'competitor_brand_id' => 'nullable|exists:competitor_brands,id',
            'product_code' => 'nullable|string|max:255',
            'product_name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:item_categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'barcode' => 'nullable|string|max:255',
            'package_size' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(CompetitorProduct::create($data), 201);
    }

    public function show(CompetitorProduct $competitorProduct)
    {
        return $competitorProduct->load(['competitor', 'brand', 'category', 'unit', 'priceSurveyItems', 'promotionItems']);
    }

    public function update(Request $request, CompetitorProduct $competitorProduct)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'competitor_brand_id' => 'nullable|exists:competitor_brands,id',
            'product_code' => 'nullable|string|max:255',
            'product_name' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:item_categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'barcode' => 'nullable|string|max:255',
            'package_size' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $competitorProduct->update($data);
        return response()->json($competitorProduct);
    }

    public function destroy(CompetitorProduct $competitorProduct)
    {
        $competitorProduct->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorProduct::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorProduct::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
