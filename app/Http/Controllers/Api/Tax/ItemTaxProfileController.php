<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\ItemTaxProfile;
use Illuminate\Http\Request;

class ItemTaxProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemTaxProfile::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('item_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $itemTaxProfiles = $query->paginate($perPage);

        return response()->json($itemTaxProfiles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required',
            'is_taxable' => 'boolean',
        ]);

        $itemTaxProfile = ItemTaxProfile::create($validated);

        return response()->json($itemTaxProfile, 201);
    }

    public function show(ItemTaxProfile $itemTaxProfile)
    {
        return response()->json($itemTaxProfile);
    }

    public function update(Request $request, ItemTaxProfile $itemTaxProfile)
    {
        $validated = $request->validate([
            'item_id' => 'sometimes|required',
            'is_taxable' => 'sometimes|boolean',
        ]);

        $itemTaxProfile->update($validated);

        return response()->json($itemTaxProfile);
    }

    public function destroy(ItemTaxProfile $itemTaxProfile)
    {
        $itemTaxProfile->delete();

        return response()->json(null, 204);
    }
}
