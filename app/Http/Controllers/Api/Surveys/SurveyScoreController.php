<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyScore;
use Illuminate\Http\Request;

class SurveyScoreController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyScore::with(['response']);

        if ($request->search) {
            $query->where('achievement_percent', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_response_id')) {
            $query->where('survey_response_id', $request->survey_response_id);
        }

        if ($request->has('min_score')) {
            $query->where('total_score', '>=', $request->min_score);
        }

        if ($request->has('max_score')) {
            $query->where('total_score', '<=', $request->max_score);
        }

        $scores = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($scores);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_response_id' => 'required|exists:survey_responses,id',
            'total_score' => 'numeric',
            'max_score' => 'numeric',
            'achievement_percent' => 'numeric|between:0,100',
        ]);

        $score = SurveyScore::create($validated);

        return response()->json($score, 201);
    }

    public function show(SurveyScore $surveyScore)
    {
        $surveyScore->load('response');
        return response()->json($surveyScore);
    }

    public function update(Request $request, SurveyScore $surveyScore)
    {
        $validated = $request->validate([
            'total_score' => 'numeric',
            'max_score' => 'numeric',
            'achievement_percent' => 'numeric|between:0,100',
        ]);

        $surveyScore->update($validated);

        return response()->json($surveyScore);
    }

    public function destroy(SurveyScore $surveyScore)
    {
        $surveyScore->delete();
        return response()->json(['message' => 'Score deleted successfully']);
    }

    public function restore($id)
    {
        $score = SurveyScore::withTrashed()->findOrFail($id);
        $score->restore();
        return response()->json(['message' => 'Score restored successfully']);
    }

    public function forceDelete($id)
    {
        $score = SurveyScore::withTrashed()->findOrFail($id);
        $score->forceDelete();
        return response()->json(['message' => 'Score permanently deleted']);
    }
}
