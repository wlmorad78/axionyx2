<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyQuestion::with(['options', 'rules']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('question_text', 'like', "%{$request->search}%")
                  ->orWhere('question_type', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('survey_id')) {
            $query->where('survey_id', $request->survey_id);
        }

        if ($request->has('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        $questions = $query->orderBy('question_no')->paginate($request->get('per_page', 15));

        return response()->json($questions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'question_no' => 'required|integer',
            'question_text' => 'required|string',
            'question_type' => 'required|in:TEXT,TEXTAREA,NUMBER,DATE,TIME,YES_NO,SINGLE_CHOICE,MULTIPLE_CHOICE,RATING,PHOTO',
            'is_required' => 'boolean',
            'allow_photo' => 'boolean',
            'allow_comment' => 'boolean',
            'display_order' => 'integer',
        ]);

        $question = SurveyQuestion::create($validated);

        return response()->json($question, 201);
    }

    public function show(SurveyQuestion $surveyQuestion)
    {
        $surveyQuestion->load(['options', 'rules.parentQuestion', 'answers']);
        return response()->json($surveyQuestion);
    }

    public function update(Request $request, SurveyQuestion $surveyQuestion)
    {
        $validated = $request->validate([
            'question_no' => 'sometimes|integer',
            'question_text' => 'sometimes|string',
            'question_type' => 'sometimes|in:TEXT,TEXTAREA,NUMBER,DATE,TIME,YES_NO,SINGLE_CHOICE,MULTIPLE_CHOICE,RATING,PHOTO',
            'is_required' => 'boolean',
            'allow_photo' => 'boolean',
            'allow_comment' => 'boolean',
            'display_order' => 'integer',
        ]);

        $surveyQuestion->update($validated);

        return response()->json($surveyQuestion);
    }

    public function destroy(SurveyQuestion $surveyQuestion)
    {
        $surveyQuestion->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    public function restore($id)
    {
        $question = SurveyQuestion::withTrashed()->findOrFail($id);
        $question->restore();
        return response()->json(['message' => 'Question restored successfully']);
    }

    public function forceDelete($id)
    {
        $question = SurveyQuestion::withTrashed()->findOrFail($id);
        $question->forceDelete();
        return response()->json(['message' => 'Question permanently deleted']);
    }
}
