<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;

class SurveyResponseController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyResponse::with(['survey', 'customer', 'salesRep', 'visit']);

        if ($request->search) {
            $query->where('response_date', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_id')) {
            $query->where('survey_id', $request->survey_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->has('response_date_from')) {
            $query->where('response_date', '>=', $request->response_date_from);
        }

        if ($request->has('response_date_to')) {
            $query->where('response_date', '<=', $request->response_date_to);
        }

        $responses = $query->orderBy('response_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($responses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'visit_id' => 'nullable|exists:customer_visits,id',
            'response_date' => 'required|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $response = SurveyResponse::create($validated);

        return response()->json($response, 201);
    }

    public function show(SurveyResponse $surveyResponse)
    {
        $surveyResponse->load(['survey.questions.options', 'customer', 'salesRep', 'visit', 'answers.question', 'answers.selectedOptions.option', 'photos', 'scores']);
        return response()->json($surveyResponse);
    }

    public function update(Request $request, SurveyResponse $surveyResponse)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'visit_id' => 'nullable|exists:customer_visits,id',
            'response_date' => 'sometimes|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $surveyResponse->update($validated);

        return response()->json($surveyResponse);
    }

    public function destroy(SurveyResponse $surveyResponse)
    {
        $surveyResponse->delete();
        return response()->json(['message' => 'Response deleted successfully']);
    }

    public function restore($id)
    {
        $response = SurveyResponse::withTrashed()->findOrFail($id);
        $response->restore();
        return response()->json(['message' => 'Response restored successfully']);
    }

    public function forceDelete($id)
    {
        $response = SurveyResponse::withTrashed()->findOrFail($id);
        $response->forceDelete();
        return response()->json(['message' => 'Response permanently deleted']);
    }
}
