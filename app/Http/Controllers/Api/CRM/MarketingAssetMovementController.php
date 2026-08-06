<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingAssetMovement;
use Illuminate\Http\Request;

class MarketingAssetMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingAssetMovement::with(['marketingAsset', 'fromCustomer', 'toCustomer', 'createdBy']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('movement_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('movement_type')) $query->where('movement_type', $request->movement_type);
        if ($request->filled('marketing_asset_id')) $query->where('marketing_asset_id', $request->marketing_asset_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_asset_id' => 'required|exists:marketing_assets,id',
            'movement_date' => 'required|date',
            'movement_type' => 'required|in:ASSIGN,RETURN,TRANSFER,MAINTENANCE,SCRAP',
            'from_customer_id' => 'nullable|exists:customers,id',
            'to_customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
        ]);

        $movement = MarketingAssetMovement::create($data);
        return response()->json($movement, 201);
    }

    public function show($id)
    {
        return MarketingAssetMovement::with(['marketingAsset', 'fromCustomer', 'toCustomer', 'createdBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $movement = MarketingAssetMovement::findOrFail($id);

        $data = $request->validate([
            'marketing_asset_id' => 'sometimes|required|exists:marketing_assets,id',
            'movement_date' => 'sometimes|required|date',
            'movement_type' => 'sometimes|required|in:ASSIGN,RETURN,TRANSFER,MAINTENANCE,SCRAP',
            'from_customer_id' => 'nullable|exists:customers,id',
            'to_customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
        ]);

        $movement->update($data);
        return $movement;
    }

    public function destroy($id)
    {
        $movement = MarketingAssetMovement::findOrFail($id);
        $movement->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $movement = MarketingAssetMovement::withTrashed()->findOrFail($id);
        $movement->restore();
        return $movement;
    }

    public function forceDelete($id)
    {
        $movement = MarketingAssetMovement::withTrashed()->findOrFail($id);
        $movement->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
