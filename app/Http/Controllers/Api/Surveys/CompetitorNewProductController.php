<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorNewProduct;
use Illuminate\Http\Request;

class CompetitorNewProductController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorNewProduct::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('report_date', 'like', "%$s%");
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
            'competitor_product_id' => 'required|exists:competitor_products,id',
            'reported_by' => 'required|exists:employees,id',
            'customer_id' => 'nullable|exists:customers,id',
            'report_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return response()->json(CompetitorNewProduct::create($data), 201);
    }

    public function show(CompetitorNewProduct $competitorNewProduct)
    {
        return $competitorNewProduct->load(['competitor', 'competitorProduct', 'reportedBy', 'customer']);
    }

    public function update(Request $request, CompetitorNewProduct $competitorNewProduct)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'competitor_product_id' => 'sometimes|required|exists:competitor_products,id',
            'reported_by' => 'sometimes|required|exists:employees,id',
            'customer_id' => 'nullable|exists:customers,id',
            'report_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $competitorNewProduct->update($data);
        return response()->json($competitorNewProduct);
    }

    public function destroy(CompetitorNewProduct $competitorNewProduct)
    {
        $competitorNewProduct->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorNewProduct::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorNewProduct::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
