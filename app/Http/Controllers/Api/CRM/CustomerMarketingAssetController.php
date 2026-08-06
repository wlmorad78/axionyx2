<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerMarketingAsset;
use Illuminate\Http\Request;

class CustomerMarketingAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerMarketingAsset::with(['marketingAsset', 'customer', 'agreement']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('marketing_asset_id')) $query->where('marketing_asset_id', $request->marketing_asset_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_asset_id' => 'required|exists:marketing_assets,id',
            'customer_id' => 'required|exists:customers,id',
            'agreement_id' => 'nullable|exists:customer_agreements,id',
            'assigned_date' => 'required|date',
            'expected_return_date' => 'nullable|date',
            'actual_return_date' => 'nullable|date',
            'status' => 'in:ASSIGNED,RETURNED,LOST,DAMAGED',
            'notes' => 'nullable|string',
        ]);

        $record = CustomerMarketingAsset::create($data);
        return response()->json($record, 201);
    }

    public function show($id)
    {
        return CustomerMarketingAsset::with(['marketingAsset', 'customer', 'agreement'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $record = CustomerMarketingAsset::findOrFail($id);

        $data = $request->validate([
            'marketing_asset_id' => 'sometimes|required|exists:marketing_assets,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'agreement_id' => 'nullable|exists:customer_agreements,id',
            'assigned_date' => 'sometimes|required|date',
            'expected_return_date' => 'nullable|date',
            'actual_return_date' => 'nullable|date',
            'status' => 'in:ASSIGNED,RETURNED,LOST,DAMAGED',
            'notes' => 'nullable|string',
        ]);

        $record->update($data);
        return $record;
    }

    public function destroy($id)
    {
        $record = CustomerMarketingAsset::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $record = CustomerMarketingAsset::withTrashed()->findOrFail($id);
        $record->restore();
        return $record;
    }

    public function forceDelete($id)
    {
        $record = CustomerMarketingAsset::withTrashed()->findOrFail($id);
        $record->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
