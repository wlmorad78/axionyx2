<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingStandard;
use Illuminate\Http\Request;

class MerchandisingStandardController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingStandard::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('standard_code')) {
            $query->where('standard_code', $request->standard_code);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $standards = $query->paginate($request->get('per_page', 15));

        return response()->json($standards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'standard_code' => 'required|string|max:50|unique:merchandising_standards,standard_code',
            'standard_name' => 'required',
            'description' => 'nullable',
            'max_score' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $standard = MerchandisingStandard::create($validated);

        return response()->json($standard, 201);
    }

    public function show($id)
    {
        $standard = MerchandisingStandard::findOrFail($id);

        return response()->json($standard);
    }

    public function update(Request $request, $id)
    {
        $standard = MerchandisingStandard::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'standard_code' => 'required|string|max:50|unique:merchandising_standards,standard_code,' . $id,
            'standard_name' => 'required',
            'description' => 'nullable',
            'max_score' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $standard->update($validated);

        return response()->json($standard);
    }

    public function destroy($id)
    {
        $standard = MerchandisingStandard::findOrFail($id);
        $standard->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $standard = MerchandisingStandard::onlyTrashed()->findOrFail($id);
        $standard->restore();

        return response()->json($standard);
    }

    public function forceDelete($id)
    {
        $standard = MerchandisingStandard::onlyTrashed()->findOrFail($id);
        $standard->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
