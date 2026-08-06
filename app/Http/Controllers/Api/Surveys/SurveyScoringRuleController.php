<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyScoringRule;
use Illuminate\Http\Request;

class SurveyScoringRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyScoringRule::with(['survey', 'question']);

        if ($request->search) {
            $query->where('expected_answer', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_id')) {
            $query->where('survey_id', $request->survey_id);
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
            'survey_id' => 'required|exists:surveys,id',
            'survey_question_id' => 'required|exists:survey_questions,id',
            'expected_answer' => 'required|string',
            'score' => 'integer',
        ]);

        $rule = SurveyScoringRule::create($validated);

        return response()->json($rule, 201);
    }

    public function show(SurveyScoringRule $surveyScoringRule)
    {
        $surveyScoringRule->load(['survey', 'question']);
        return response()->json($surveyScoringRule);
    }

    public function update(Request $request, SurveyScoringRule $surveyScoringRule)
    {
        $validated = $request->validate([
            'survey_question_id' => 'sometimes|exists:survey_questions,id',
            'expected_answer' => 'sometimes|string',
            'score' => 'integer',
        ]);

        $surveyScoringRule->update($validated);

        return response()->json($surveyScoringRule);
    }

    public function destroy(SurveyScoringRule $surveyScoringRule)
    {
        $surveyScoringRule->delete();
        return response()->json(['message' => 'Scoring rule deleted successfully']);
    }

    public function restore($id)
    {
        $rule = SurveyScoringRule::withTrashed()->findOrFail($id);
        $rule->restore();
        return response()->json(['message' => 'Scoring rule restored successfully']);
    }

    public function forceDelete($id)
    {
        $rule = SurveyScoringRule::withTrashed()->findOrFail($id);
        $rule->forceDelete();
        return response()->json(['message' => 'Scoring rule permanently deleted']);
    }
}
