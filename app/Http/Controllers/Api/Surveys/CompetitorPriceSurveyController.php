<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPriceSurvey;
use Illuminate\Http\Request;

class CompetitorPriceSurveyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPriceSurvey::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('survey_date', 'like', "%$s%");
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
            'sales_rep_id' => 'required|exists:employees,id',
            'customer_id' => 'nullable|exists:customers,id',
            'visit_id' => 'nullable|exists:customer_visits,id',
            'survey_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return response()->json(CompetitorPriceSurvey::create($data), 201);
    }

    public function show(CompetitorPriceSurvey $competitorPriceSurvey)
    {
        return $competitorPriceSurvey->load(['company', 'salesRep', 'customer', 'visit', 'items']);
    }

    public function update(Request $request, CompetitorPriceSurvey $competitorPriceSurvey)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'sales_rep_id' => 'sometimes|required|exists:employees,id',
            'customer_id' => 'nullable|exists:customers,id',
            'visit_id' => 'nullable|exists:customer_visits,id',
            'survey_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $competitorPriceSurvey->update($data);
        return response()->json($competitorPriceSurvey);
    }

    public function destroy(CompetitorPriceSurvey $competitorPriceSurvey)
    {
        $competitorPriceSurvey->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CompetitorPriceSurvey::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CompetitorPriceSurvey::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
