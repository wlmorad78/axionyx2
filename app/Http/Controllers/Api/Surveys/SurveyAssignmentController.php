<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyAssignment;
use Illuminate\Http\Request;

class SurveyAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyAssignment::with(['survey', 'salesRep', 'route', 'customer']);

        if ($request->search) {
            $query->where('status', 'like', "%{$request->search}%");
        }

        if ($request->has('survey_id')) {
            $query->where('survey_id', $request->survey_id);
        }

        if ($request->has('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_date_from')) {
            $query->where('assigned_date', '>=', $request->assigned_date_from);
        }

        if ($request->has('assigned_date_to')) {
            $query->where('assigned_date', '<=', $request->assigned_date_to);
        }

        $assignments = $query->orderBy('assigned_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'route_id' => 'nullable|exists:routes,id',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_date' => 'required|date',
            'status' => 'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
        ]);

        $assignment = SurveyAssignment::create($validated);

        return response()->json($assignment, 201);
    }

    public function show(SurveyAssignment $surveyAssignment)
    {
        $surveyAssignment->load(['survey', 'salesRep', 'route', 'customer']);
        return response()->json($surveyAssignment);
    }

    public function update(Request $request, SurveyAssignment $surveyAssignment)
    {
        $validated = $request->validate([
            'sales_rep_id' => 'nullable|exists:employees,id',
            'route_id' => 'nullable|exists:routes,id',
            'customer_id' => 'nullable|exists:customers,id',
            'assigned_date' => 'sometimes|date',
            'status' => 'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
        ]);

        $surveyAssignment->update($validated);

        return response()->json($surveyAssignment);
    }

    public function destroy(SurveyAssignment $surveyAssignment)
    {
        $surveyAssignment->delete();
        return response()->json(['message' => 'Assignment deleted successfully']);
    }

    public function restore($id)
    {
        $assignment = SurveyAssignment::withTrashed()->findOrFail($id);
        $assignment->restore();
        return response()->json(['message' => 'Assignment restored successfully']);
    }

    public function forceDelete($id)
    {
        $assignment = SurveyAssignment::withTrashed()->findOrFail($id);
        $assignment->forceDelete();
        return response()->json(['message' => 'Assignment permanently deleted']);
    }
}
