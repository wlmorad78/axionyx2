<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\DisplayLocation;
use Illuminate\Http\Request;

class DisplayLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = DisplayLocation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('location_code')) {
            $query->where('location_code', $request->location_code);
        }

        $locations = $query->paginate($request->get('per_page', 15));

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'location_code' => 'required|string|max:50|unique:display_locations,location_code',
            'location_name' => 'required',
            'description' => 'nullable',
        ]);

        $location = DisplayLocation::create($validated);

        return response()->json($location, 201);
    }

    public function show($id)
    {
        $location = DisplayLocation::findOrFail($id);

        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $location = DisplayLocation::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'location_code' => 'required|string|max:50|unique:display_locations,location_code,' . $id,
            'location_name' => 'required',
            'description' => 'nullable',
        ]);

        $location->update($validated);

        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = DisplayLocation::findOrFail($id);
        $location->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $location = DisplayLocation::onlyTrashed()->findOrFail($id);
        $location->restore();

        return response()->json($location);
    }

    public function forceDelete($id)
    {
        $location = DisplayLocation::onlyTrashed()->findOrFail($id);
        $location->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
