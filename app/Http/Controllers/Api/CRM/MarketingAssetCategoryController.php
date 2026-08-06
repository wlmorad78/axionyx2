<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingAssetCategory;
use Illuminate\Http\Request;

class MarketingAssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingAssetCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('is_active', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:255|unique:marketing_asset_categories,code',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category = MarketingAssetCategory::create($data);
        return response()->json($category, 201);
    }

    public function show($id)
    {
        return MarketingAssetCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = MarketingAssetCategory::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'code' => 'sometimes|required|string|max:255|unique:marketing_asset_categories,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category->update($data);
        return $category;
    }

    public function destroy($id)
    {
        $category = MarketingAssetCategory::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $category = MarketingAssetCategory::withTrashed()->findOrFail($id);
        $category->restore();
        return $category;
    }

    public function forceDelete($id)
    {
        $category = MarketingAssetCategory::withTrashed()->findOrFail($id);
        $category->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
