<?php

namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\SupplierTaxProfile;
use Illuminate\Http\Request;

class SupplierTaxProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierTaxProfile::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('supplier_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $supplierTaxProfiles = $query->paginate($perPage);

        return response()->json($supplierTaxProfiles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required',
            'is_taxable' => 'boolean',
        ]);

        $supplierTaxProfile = SupplierTaxProfile::create($validated);

        return response()->json($supplierTaxProfile, 201);
    }

    public function show(SupplierTaxProfile $supplierTaxProfile)
    {
        return response()->json($supplierTaxProfile);
    }

    public function update(Request $request, SupplierTaxProfile $supplierTaxProfile)
    {
        $validated = $request->validate([
            'supplier_id' => 'sometimes|required',
            'is_taxable' => 'sometimes|boolean',
        ]);

        $supplierTaxProfile->update($validated);

        return response()->json($supplierTaxProfile);
    }

    public function destroy(SupplierTaxProfile $supplierTaxProfile)
    {
        $supplierTaxProfile->delete();

        return response()->json(null, 204);
    }
}
