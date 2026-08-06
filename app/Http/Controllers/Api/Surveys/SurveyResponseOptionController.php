<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponseOption;
use Illuminate\Http\Request;

class SurveyResponseOptionController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyResponseOption::with(['answer', 'option']);

        if ($request->has('survey_response_answer_id')) {
            $query->where('survey_response_answer_id', $request->survey_response_answer_id);
        }

        $options = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($options);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_response_answer_id' => 'required|exists:survey_response_answers,id',
            'survey_question_option_id' => 'required|exists:survey_question_options,id',
        ]);

        $option = SurveyResponseOption::create($validated);

        return response()->json($option, 201);
    }

    public function show(SurveyResponseOption $surveyResponseOption)
    {
        $surveyResponseOption->load(['answer', 'option']);
        return response()->json($surveyResponseOption);
    }

    public function update(Request $request, SurveyResponseOption $surveyResponseOption)
    {
        $validated = $request->validate([
            'survey_question_option_id' => 'sometimes|exists:survey_question_options,id',
        ]);

        $surveyResponseOption->update($validated);

        return response()->json($surveyResponseOption);
    }

    public function destroy(SurveyResponseOption $surveyResponseOption)
    {
        $surveyResponseOption->delete();
        return response()->json(['message' => 'Response option deleted successfully']);
    }

    public function restore($id)
    {
        $option = SurveyResponseOption::withTrashed()->findOrFail($id);
        $option->restore();
        return response()->json(['message' => 'Response option restored successfully']);
    }

    public function forceDelete($id)
    {
        $option = SurveyResponseOption::withTrashed()->findOrFail($id);
        $option->forceDelete();
        return response()->json(['message' => 'Response option permanently deleted']);
    }
}
