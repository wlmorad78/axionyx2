<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponsePhoto;
use Illuminate\Http\Request;

class SurveyResponsePhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyResponsePhoto::with(['response', 'question']);

        if ($request->search) {
            $query->where('file_path', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_response_id')) {
            $query->where('survey_response_id', $request->survey_response_id);
        }

        if ($request->has('survey_question_id')) {
            $query->where('survey_question_id', $request->survey_question_id);
        }

        $photos = $query->orderBy('taken_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($photos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_response_id' => 'required|exists:survey_responses,id',
            'survey_question_id' => 'nullable|exists:survey_questions,id',
            'file_path' => 'required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $photo = SurveyResponsePhoto::create($validated);

        return response()->json($photo, 201);
    }

    public function show(SurveyResponsePhoto $surveyResponsePhoto)
    {
        $surveyResponsePhoto->load(['response', 'question']);
        return response()->json($surveyResponsePhoto);
    }

    public function update(Request $request, SurveyResponsePhoto $surveyResponsePhoto)
    {
        $validated = $request->validate([
            'survey_question_id' => 'nullable|exists:survey_questions,id',
            'file_path' => 'sometimes|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $surveyResponsePhoto->update($validated);

        return response()->json($surveyResponsePhoto);
    }

    public function destroy(SurveyResponsePhoto $surveyResponsePhoto)
    {
        $surveyResponsePhoto->delete();
        return response()->json(['message' => 'Photo deleted successfully']);
    }

    public function restore($id)
    {
        $photo = SurveyResponsePhoto::withTrashed()->findOrFail($id);
        $photo->restore();
        return response()->json(['message' => 'Photo restored successfully']);
    }

    public function forceDelete($id)
    {
        $photo = SurveyResponsePhoto::withTrashed()->findOrFail($id);
        $photo->forceDelete();
        return response()->json(['message' => 'Photo permanently deleted']);
    }
}
