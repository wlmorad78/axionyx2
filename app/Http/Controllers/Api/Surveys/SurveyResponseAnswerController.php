<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponseAnswer;
use Illuminate\Http\Request;

class SurveyResponseAnswerController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyResponseAnswer::with(['response', 'question', 'selectedOptions.option']);

        if ($request->search) {
            $query->where('answer_text', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_response_id')) {
            $query->where('survey_response_id', $request->survey_response_id);
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $answers = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($answers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_response_id' => 'required|exists:survey_responses,id',
            'survey_question_id' => 'required|exists:survey_questions,id',
            'answer_text' => 'nullable|string',
            'answer_numeric' => 'nullable|numeric',
            'answer_date' => 'nullable|date',
            'selected_options' => 'array',
            'selected_options.*' => 'exists:survey_question_options,id',
        ]);

        $answer = SurveyResponseAnswer::create(collect($validated)->only([
            'survey_response_id', 'survey_question_id', 'answer_text', 'answer_numeric', 'answer_date',
        ])->toArray());

        if (!empty($validated['selected_options'])) {
            foreach ($validated['selected_options'] as $optionId) {
                $answer->selectedOptions()->create([
                    'survey_question_option_id' => $optionId,
                ]);
            }
        }

        return response()->json($answer->load('selectedOptions'), 201);
    }

    public function show(SurveyResponseAnswer $surveyResponseAnswer)
    {
        $surveyResponseAnswer->load(['response', 'question', 'selectedOptions.option']);
        return response()->json($surveyResponseAnswer);
    }

    public function update(Request $request, SurveyResponseAnswer $surveyResponseAnswer)
    {
        $validated = $request->validate([
            'answer_text' => 'nullable|string',
            'answer_numeric' => 'nullable|numeric',
            'answer_date' => 'nullable|date',
            'selected_options' => 'array',
            'selected_options.*' => 'exists:survey_question_options,id',
        ]);

        $surveyResponseAnswer->update(collect($validated)->only([
            'answer_text', 'answer_numeric', 'answer_date',
        ])->toArray());

        if (isset($validated['selected_options'])) {
            $surveyResponseAnswer->selectedOptions()->delete();
            foreach ($validated['selected_options'] as $optionId) {
                $surveyResponseAnswer->selectedOptions()->create([
                    'survey_question_option_id' => $optionId,
                ]);
            }
        }

        return response()->json($surveyResponseAnswer->load('selectedOptions'));
    }

    public function destroy(SurveyResponseAnswer $surveyResponseAnswer)
    {
        $surveyResponseAnswer->delete();
        return response()->json(['message' => 'Answer deleted successfully']);
    }

    public function restore($id)
    {
        $answer = SurveyResponseAnswer::withTrashed()->findOrFail($id);
        $answer->restore();
        return response()->json(['message' => 'Answer restored successfully']);
    }

    public function forceDelete($id)
    {
        $answer = SurveyResponseAnswer::withTrashed()->findOrFail($id);
        $answer->forceDelete();
        return response()->json(['message' => 'Answer permanently deleted']);
    }
}
