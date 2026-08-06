<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\Tax\CustomerTaxProfile;
use Illuminate\Http\Request;

class CustomerTaxProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerTaxProfile::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('customer_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $customerTaxProfiles = $query->paginate($perPage);

        return response()->json($customerTaxProfiles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required',
            'is_taxable' => 'boolean',
        ]);

        $customerTaxProfile = CustomerTaxProfile::create($validated);

        return response()->json($customerTaxProfile, 201);
    }

    public function show(CustomerTaxProfile $customerTaxProfile)
    {
        return response()->json($customerTaxProfile);
    }

    public function update(Request $request, CustomerTaxProfile $customerTaxProfile)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required',
            'is_taxable' => 'sometimes|boolean',
        ]);

        $customerTaxProfile->update($validated);

        return response()->json($customerTaxProfile);
    }

    public function destroy(CustomerTaxProfile $customerTaxProfile)
    {
        $customerTaxProfile->delete();

        return response()->json(null, 204);
    }
}
