<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingAsset;
use Illuminate\Http\Request;

class MarketingAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingAsset::with('category');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('asset_code', 'like', "%{$s}%")
                    ->orWhere('asset_name', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%")
                    ->orWhere('serial_no', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category_id')) $query->where('marketing_asset_category_id', $request->category_id);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'asset_code' => 'required|string|max:255',
            'marketing_asset_category_id' => 'required|exists:marketing_asset_categories,id',
            'serial_no' => 'nullable|string|unique:marketing_assets,serial_no',
            'asset_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'numeric|min:0',
            'current_value' => 'numeric|min:0',
            'status' => 'in:AVAILABLE,ASSIGNED,UNDER_MAINTENANCE,DAMAGED,SCRAPPED',
            'notes' => 'nullable|string',
        ]);

        $asset = MarketingAsset::create($data);
        return response()->json($asset, 201);
    }

    public function show($id)
    {
        return MarketingAsset::with(['category', 'customerAssets', 'movements', 'maintenance'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $asset = MarketingAsset::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'asset_code' => 'sometimes|required|string|max:255',
            'marketing_asset_category_id' => 'sometimes|required|exists:marketing_asset_categories,id',
            'serial_no' => 'nullable|string|unique:marketing_assets,serial_no,' . $id,
            'asset_name' => 'sometimes|required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'numeric|min:0',
            'current_value' => 'numeric|min:0',
            'status' => 'in:AVAILABLE,ASSIGNED,UNDER_MAINTENANCE,DAMAGED,SCRAPPED',
            'notes' => 'nullable|string',
        ]);

        $asset->update($data);
        return $asset;
    }

    public function destroy($id)
    {
        $asset = MarketingAsset::findOrFail($id);
        $asset->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $asset = MarketingAsset::withTrashed()->findOrFail($id);
        $asset->restore();
        return $asset;
    }

    public function forceDelete($id)
    {
        $asset = MarketingAsset::withTrashed()->findOrFail($id);
        $asset->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
