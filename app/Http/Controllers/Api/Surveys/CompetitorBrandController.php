<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorBrand;
use Illuminate\Http\Request;

class CompetitorBrandController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorBrand::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
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
            'competitor_id' => 'required|exists:competitors,id',
            'brand_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(CompetitorBrand::create($data), 201);
    }

    public function show(CompetitorBrand $competitorBrand)
    {
        return $competitorBrand->load(['competitor', 'products']);
    }

    public function update(Request $request, CompetitorBrand $competitorBrand)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'brand_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $competitorBrand->update($data);
        return response()->json($competitorBrand);
    }

    public function destroy(CompetitorBrand $competitorBrand)
    {
        $competitorBrand->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorBrand::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorBrand::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
