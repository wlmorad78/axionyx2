<?php

namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetCategory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset_category', 'create'));
        $assetCategory = AssetCategory::create($data);
        return response()->json($assetCategory, 201);
    }

    public function show($id)
    {
        return AssetCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $assetCategory = AssetCategory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset_category', 'update', $assetCategory));
        $assetCategory->update($data);
        return $assetCategory;
    }

    public function destroy($id)
    {
        $assetCategory = AssetCategory::findOrFail($id);
        $assetCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $assetCategory = AssetCategory::withTrashed()->findOrFail($id);
        $assetCategory->restore();
        return $assetCategory;
    }

    public function forceDelete($id)
    {
        $assetCategory = AssetCategory::withTrashed()->findOrFail($id);
        $assetCategory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
