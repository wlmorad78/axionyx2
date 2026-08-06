<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SalesTarget};
use App\Support\ValidationRules;

class SalesTargetController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesTarget::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('year', 'like', "%{$s}%")
                  ->orWhere('month', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_target', 'create'));
        $salesTarget = SalesTarget::create($data);
        return response()->json($salesTarget, 201);
    }

    public function show($id)
    {
        return SalesTarget::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $salesTarget = SalesTarget::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sales_target', 'update', $salesTarget));
        $salesTarget->update($data);
        return $salesTarget;
    }

    public function destroy($id)
    {
        $salesTarget = SalesTarget::findOrFail($id);
        $salesTarget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $salesTarget = SalesTarget::withTrashed()->findOrFail($id);
        $salesTarget->restore();
        return $salesTarget;
    }

    public function forceDelete($id)
    {
        $salesTarget = SalesTarget::withTrashed()->findOrFail($id);
        $salesTarget->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
