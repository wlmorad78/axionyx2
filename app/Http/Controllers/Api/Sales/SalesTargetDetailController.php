<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SalesTargetDetail};
use App\Support\ValidationRules;

class SalesTargetDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesTargetDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('target_amount', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_target_detail', 'create'));
        $salesTargetDetail = SalesTargetDetail::create($data);
        return response()->json($salesTargetDetail, 201);
    }

    public function show($id)
    {
        return SalesTargetDetail::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $salesTargetDetail = SalesTargetDetail::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sales_target_detail', 'update', $salesTargetDetail));
        $salesTargetDetail->update($data);
        return $salesTargetDetail;
    }

    public function destroy($id)
    {
        $salesTargetDetail = SalesTargetDetail::findOrFail($id);
        $salesTargetDetail->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $salesTargetDetail = SalesTargetDetail::withTrashed()->findOrFail($id);
        $salesTargetDetail->restore();
        return $salesTargetDetail;
    }

    public function forceDelete($id)
    {
        $salesTargetDetail = SalesTargetDetail::withTrashed()->findOrFail($id);
        $salesTargetDetail->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
