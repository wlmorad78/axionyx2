<?php

namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\SurveyCategory;
use Illuminate\Http\Request;

class SurveyCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:255|unique:survey_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = SurveyCategory::create($validated);

        return response()->json($category, 201);
    }

    public function show(SurveyCategory $surveyCategory)
    {
        $surveyCategory->load('surveys');
        return response()->json($surveyCategory);
    }

    public function update(Request $request, SurveyCategory $surveyCategory)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:255|unique:survey_categories,code,' . $surveyCategory->id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $surveyCategory->update($validated);

        return response()->json($surveyCategory);
    }

    public function destroy(SurveyCategory $surveyCategory)
    {
        $surveyCategory->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }

    public function restore($id)
    {
        $category = SurveyCategory::withTrashed()->findOrFail($id);
        $category->restore();
        return response()->json(['message' => 'Category restored successfully']);
    }

    public function forceDelete($id)
    {
        $category = SurveyCategory::withTrashed()->findOrFail($id);
        $category->forceDelete();
        return response()->json(['message' => 'Category permanently deleted']);
    }
}
