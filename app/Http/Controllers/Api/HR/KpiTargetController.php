<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{KpiTarget};
use App\Support\ValidationRules;

class KpiTargetController extends Controller
{
    public function index(Request $request)
    {
        $query = KpiTarget::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('target_value', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('kpi_target', 'create'));
        $kpiTarget = KpiTarget::create($data);
        return response()->json($kpiTarget, 201);
    }

    public function show($id)
    {
        return KpiTarget::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $kpiTarget = KpiTarget::findOrFail($id);
        $data = $request->validate(ValidationRules::for('kpi_target', 'update', $kpiTarget));
        $kpiTarget->update($data);
        return $kpiTarget;
    }

    public function destroy($id)
    {
        $kpiTarget = KpiTarget::findOrFail($id);
        $kpiTarget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $kpiTarget = KpiTarget::withTrashed()->findOrFail($id);
        $kpiTarget->restore();
        return $kpiTarget;
    }

    public function forceDelete($id)
    {
        $kpiTarget = KpiTarget::withTrashed()->findOrFail($id);
        $kpiTarget->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
