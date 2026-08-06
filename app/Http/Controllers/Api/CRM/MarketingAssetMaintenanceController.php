<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingAssetMaintenance;
use Illuminate\Http\Request;

class MarketingAssetMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingAssetMaintenance::with('marketingAsset');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('maintenance_type', 'like', "%{$s}%")
                    ->orWhere('vendor_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('marketing_asset_id')) $query->where('marketing_asset_id', $request->marketing_asset_id);
        if ($request->filled('maintenance_type')) $query->where('maintenance_type', $request->maintenance_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_asset_id' => 'required|exists:marketing_assets,id',
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|string|max:255',
            'cost' => 'numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $maintenance = MarketingAssetMaintenance::create($data);
        return response()->json($maintenance, 201);
    }

    public function show($id)
    {
        return MarketingAssetMaintenance::with('marketingAsset')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $maintenance = MarketingAssetMaintenance::findOrFail($id);

        $data = $request->validate([
            'marketing_asset_id' => 'sometimes|required|exists:marketing_assets,id',
            'maintenance_date' => 'sometimes|required|date',
            'maintenance_type' => 'sometimes|required|string|max:255',
            'cost' => 'numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $maintenance->update($data);
        return $maintenance;
    }

    public function destroy($id)
    {
        $maintenance = MarketingAssetMaintenance::findOrFail($id);
        $maintenance->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $maintenance = MarketingAssetMaintenance::withTrashed()->findOrFail($id);
        $maintenance->restore();
        return $maintenance;
    }

    public function forceDelete($id)
    {
        $maintenance = MarketingAssetMaintenance::withTrashed()->findOrFail($id);
        $maintenance->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
