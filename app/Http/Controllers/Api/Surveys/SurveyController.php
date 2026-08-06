<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\Surveys\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $query = Survey::with(['category', 'createdBy']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('survey_code', 'like', "%{$request->search}%")
                  ->orWhere('survey_name', 'like', "%{$request->search}%")
                  ->orWhere('status', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('survey_category_id')) {
            $query->where('survey_category_id', $request->survey_category_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($surveys);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'survey_category_id' => 'required|exists:survey_categories,id',
            'survey_code' => 'required|string|max:255',
            'survey_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_mandatory' => 'boolean',
            'status' => 'in:DRAFT,ACTIVE,INACTIVE,CLOSED',
            'created_by' => 'nullable|exists:users,id',
        ]);

        $survey = Survey::create($validated);

        return response()->json($survey, 201);
    }

    public function show(Survey $survey)
    {
        $survey->load(['category', 'questions.options', 'createdBy', 'scoringRules', 'assignments']);
        return response()->json($survey);
    }

    public function update(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'survey_category_id' => 'sometimes|exists:survey_categories,id',
            'survey_code' => 'sometimes|string|max:255',
            'survey_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_mandatory' => 'boolean',
            'status' => 'in:DRAFT,ACTIVE,INACTIVE,CLOSED',
        ]);

        $survey->update($validated);

        return response()->json($survey);
    }

    public function destroy(Survey $survey)
    {
        $survey->delete();
        return response()->json(['message' => 'Survey deleted successfully']);
    }

    public function restore($id)
    {
        $survey = Survey::withTrashed()->findOrFail($id);
        $survey->restore();
        return response()->json(['message' => 'Survey restored successfully']);
    }

    public function forceDelete($id)
    {
        $survey = Survey::withTrashed()->findOrFail($id);
        $survey->forceDelete();
        return response()->json(['message' => 'Survey permanently deleted']);
    }
}
