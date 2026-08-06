<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\Surveys\Competitor;
use Illuminate\Http\Request;

class CompetitorController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Competitor::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('competitor_code', 'like', "%$s%")
                    ->orWhere('competitor_name', 'like', "%$s%")
                    ->orWhere('contact_person', 'like', "%$s%");
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
            'company_id' => 'required|exists:companies,id',
            'competitor_code' => 'required|string|max:255',
            'competitor_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(Competitor::create($data), 201);
    }

    public function show(Competitor $competitor)
    {
        return $competitor->load(['company', 'brands', 'products', 'promotions', 'newProducts', 'photos']);
    }

    public function update(Request $request, Competitor $competitor)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'competitor_code' => 'sometimes|required|string|max:255',
            'competitor_name' => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $competitor->update($data);
        return response()->json($competitor);
    }

    public function destroy(Competitor $competitor)
    {
        $competitor->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = Competitor::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        Competitor::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
