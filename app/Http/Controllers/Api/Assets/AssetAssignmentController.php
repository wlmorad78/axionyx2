<?php

namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetAssignment::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset_assignment', 'create'));
        $assetAssignment = AssetAssignment::create($data);
        return response()->json($assetAssignment, 201);
    }

    public function show($id)
    {
        return AssetAssignment::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $assetAssignment = AssetAssignment::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset_assignment', 'update', $assetAssignment));
        $assetAssignment->update($data);
        return $assetAssignment;
    }

    public function destroy($id)
    {
        $assetAssignment = AssetAssignment::findOrFail($id);
        $assetAssignment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $assetAssignment = AssetAssignment::withTrashed()->findOrFail($id);
        $assetAssignment->restore();
        return $assetAssignment;
    }

    public function forceDelete($id)
    {
        $assetAssignment = AssetAssignment::withTrashed()->findOrFail($id);
        $assetAssignment->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
