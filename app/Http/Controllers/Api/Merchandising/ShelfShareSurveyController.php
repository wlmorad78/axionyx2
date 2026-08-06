<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfShareSurvey;
use Illuminate\Http\Request;

class ShelfShareSurveyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ShelfShareSurvey::with($with);

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
            'customer_id' => 'required|exists:customers,id',
            'sales_rep_id' => 'required|exists:employees,id',
            'survey_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return response()->json(ShelfShareSurvey::create($data), 201);
    }

    public function show(ShelfShareSurvey $shelfShareSurvey)
    {
        return $shelfShareSurvey->load(['company', 'customer', 'salesRep', 'items']);
    }

    public function update(Request $request, ShelfShareSurvey $shelfShareSurvey)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'sales_rep_id' => 'sometimes|required|exists:employees,id',
            'survey_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $shelfShareSurvey->update($data);
        return response()->json($shelfShareSurvey);
    }

    public function destroy(ShelfShareSurvey $shelfShareSurvey)
    {
        $shelfShareSurvey->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ShelfShareSurvey::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ShelfShareSurvey::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
