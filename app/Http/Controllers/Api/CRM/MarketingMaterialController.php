<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingMaterial;
use Illuminate\Http\Request;

class MarketingMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingMaterial::with('unit');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('material_code', 'like', "%{$s}%")
                    ->orWhere('material_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'material_code' => 'required|string|max:255',
            'material_name' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'cost' => 'numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $material = MarketingMaterial::create($data);
        return response()->json($material, 201);
    }

    public function show($id)
    {
        return MarketingMaterial::with(['unit', 'customerMaterials'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $material = MarketingMaterial::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'material_code' => 'sometimes|required|string|max:255',
            'material_name' => 'sometimes|required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'cost' => 'numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $material->update($data);
        return $material;
    }

    public function destroy($id)
    {
        $material = MarketingMaterial::findOrFail($id);
        $material->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $material = MarketingMaterial::withTrashed()->findOrFail($id);
        $material->restore();
        return $material;
    }

    public function forceDelete($id)
    {
        $material = MarketingMaterial::withTrashed()->findOrFail($id);
        $material->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
