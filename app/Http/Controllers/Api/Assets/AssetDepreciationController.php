<?php

namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\AssetDepreciation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetDepreciationController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetDepreciation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset_depreciation', 'create'));
        $assetDepreciation = AssetDepreciation::create($data);
        return response()->json($assetDepreciation, 201);
    }

    public function show($id)
    {
        return AssetDepreciation::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $assetDepreciation = AssetDepreciation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset_depreciation', 'update', $assetDepreciation));
        $assetDepreciation->update($data);
        return $assetDepreciation;
    }

    public function destroy($id)
    {
        $assetDepreciation = AssetDepreciation::findOrFail($id);
        $assetDepreciation->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $assetDepreciation = AssetDepreciation::withTrashed()->findOrFail($id);
        $assetDepreciation->restore();
        return $assetDepreciation;
    }

    public function forceDelete($id)
    {
        $assetDepreciation = AssetDepreciation::withTrashed()->findOrFail($id);
        $assetDepreciation->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
