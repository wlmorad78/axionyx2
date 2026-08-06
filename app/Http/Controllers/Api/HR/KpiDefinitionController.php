<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{KpiDefinition};
use App\Support\ValidationRules;

class KpiDefinitionController extends Controller
{
    public function index(Request $request)
    {
        $query = KpiDefinition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('kpi_code', 'like', "%{$s}%")
                  ->orWhere('kpi_name', 'like', "%{$s}%")
                  ->orWhere('module', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('kpi_definition', 'create'));
        $kpiDefinition = KpiDefinition::create($data);
        return response()->json($kpiDefinition, 201);
    }

    public function show($id)
    {
        return KpiDefinition::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $kpiDefinition = KpiDefinition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('kpi_definition', 'update', $kpiDefinition));
        $kpiDefinition->update($data);
        return $kpiDefinition;
    }

    public function destroy($id)
    {
        $kpiDefinition = KpiDefinition::findOrFail($id);
        $kpiDefinition->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $kpiDefinition = KpiDefinition::withTrashed()->findOrFail($id);
        $kpiDefinition->restore();
        return $kpiDefinition;
    }

    public function forceDelete($id)
    {
        $kpiDefinition = KpiDefinition::withTrashed()->findOrFail($id);
        $kpiDefinition->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
