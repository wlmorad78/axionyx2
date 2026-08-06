<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestionRule;
use Illuminate\Http\Request;

class SurveyQuestionRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyQuestionRule::with(['question', 'parentQuestion']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('operator', 'like', "%{$request->search}%")
                  ->orWhere('action_type', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $rules = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($rules);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_question_id' => 'required|exists:survey_questions,id',
            'parent_question_id' => 'required|exists:survey_questions,id',
            'operator' => 'required|in:=,!=,>,<,>=,<=',
            'expected_value' => 'required|string',
            'action_type' => 'required|in:SHOW,HIDE,REQUIRE',
        ]);

        $rule = SurveyQuestionRule::create($validated);

        return response()->json($rule, 201);
    }

    public function show(SurveyQuestionRule $surveyQuestionRule)
    {
        $surveyQuestionRule->load(['question', 'parentQuestion']);
        return response()->json($surveyQuestionRule);
    }

    public function update(Request $request, SurveyQuestionRule $surveyQuestionRule)
    {
        $validated = $request->validate([
            'parent_question_id' => 'sometimes|exists:survey_questions,id',
            'operator' => 'sometimes|in:=,!=,>,<,>=,<=',
            'expected_value' => 'sometimes|string',
            'action_type' => 'sometimes|in:SHOW,HIDE,REQUIRE',
        ]);

        $surveyQuestionRule->update($validated);

        return response()->json($surveyQuestionRule);
    }

    public function destroy(SurveyQuestionRule $surveyQuestionRule)
    {
        $surveyQuestionRule->delete();
        return response()->json(['message' => 'Rule deleted successfully']);
    }

    public function restore($id)
    {
        $rule = SurveyQuestionRule::withTrashed()->findOrFail($id);
        $rule->restore();
        return response()->json(['message' => 'Rule restored successfully']);
    }

    public function forceDelete($id)
    {
        $rule = SurveyQuestionRule::withTrashed()->findOrFail($id);
        $rule->forceDelete();
        return response()->json(['message' => 'Rule permanently deleted']);
    }
}
