<?php

namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('asset_code', 'like', "%{$s}%")
                    ->orWhere('asset_name', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset', 'create'));
        $asset = Asset::create($data);
        return response()->json($asset, 201);
    }

    public function show($id)
    {
        return Asset::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset', 'update', $asset));
        $asset->update($data);
        return $asset;
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $asset = Asset::withTrashed()->findOrFail($id);
        $asset->restore();
        return $asset;
    }

    public function forceDelete($id)
    {
        $asset = Asset::withTrashed()->findOrFail($id);
        $asset->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
