<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaignCustomer;
use Illuminate\Http\Request;

class MarketingCampaignCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingCampaignCustomer::with(['campaign', 'customer']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('target_amount', 'like', "%{$s}%");
            });
        }

        if ($request->filled('marketing_campaign_id')) $query->where('marketing_campaign_id', $request->marketing_campaign_id);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_campaign_id' => 'required|exists:marketing_campaigns,id',
            'customer_id' => 'required|exists:customers,id',
            'target_amount' => 'numeric|min:0',
            'actual_amount' => 'numeric|min:0',
        ]);

        $record = MarketingCampaignCustomer::create($data);
        return response()->json($record, 201);
    }

    public function show($id)
    {
        return MarketingCampaignCustomer::with(['campaign', 'customer'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $record = MarketingCampaignCustomer::findOrFail($id);

        $data = $request->validate([
            'marketing_campaign_id' => 'sometimes|required|exists:marketing_campaigns,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'target_amount' => 'numeric|min:0',
            'actual_amount' => 'numeric|min:0',
        ]);

        $record->update($data);
        return $record;
    }

    public function destroy($id)
    {
        $record = MarketingCampaignCustomer::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $record = MarketingCampaignCustomer::withTrashed()->findOrFail($id);
        $record->restore();
        return $record;
    }

    public function forceDelete($id)
    {
        $record = MarketingCampaignCustomer::withTrashed()->findOrFail($id);
        $record->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
