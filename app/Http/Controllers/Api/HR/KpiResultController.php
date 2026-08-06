<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{KpiResult};
use App\Support\ValidationRules;

class KpiResultController extends Controller
{
    public function index(Request $request)
    {
        $query = KpiResult::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('actual_value', 'like', "%{$s}%")
                  ->orWhere('achievement_percent', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('kpi_result', 'create'));
        $kpiResult = KpiResult::create($data);
        return response()->json($kpiResult, 201);
    }

    public function show($id)
    {
        return KpiResult::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $kpiResult = KpiResult::findOrFail($id);
        $data = $request->validate(ValidationRules::for('kpi_result', 'update', $kpiResult));
        $kpiResult->update($data);
        return $kpiResult;
    }

    public function destroy($id)
    {
        $kpiResult = KpiResult::findOrFail($id);
        $kpiResult->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $kpiResult = KpiResult::withTrashed()->findOrFail($id);
        $kpiResult->restore();
        return $kpiResult;
    }

    public function forceDelete($id)
    {
        $kpiResult = KpiResult::withTrashed()->findOrFail($id);
        $kpiResult->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
