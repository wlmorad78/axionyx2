<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryAdjustment;
use Illuminate\Http\Request;

class TreasuryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['treasury'];
        $query = TreasuryAdjustment::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_id) {
            $query->where('treasury_id', $request->treasury_id);
        }
        if ($request->adjustment_type) {
            $query->where('adjustment_type', $request->adjustment_type);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_id' => 'required',
            'adjustment_no' => 'required|unique:treasury_adjustments,adjustment_no',
            'adjustment_date' => 'required|date',
            'adjustment_type' => 'required|in:SHORTAGE,OVERAGE,CORRECTION',
            'amount' => 'nullable|numeric',
            'reason' => 'nullable',
            'notes' => 'nullable',
        ]);

        $adjustment = TreasuryAdjustment::create($data);
        return response()->json($adjustment, 201);
    }

    public function show($id)
    {
        $adjustment = TreasuryAdjustment::with(['treasury'])->findOrFail($id);
        return response()->json($adjustment);
    }

    public function update(Request $request, $id)
    {
        $adjustment = TreasuryAdjustment::findOrFail($id);

        $data = $request->validate([
            'treasury_id' => 'required',
            'adjustment_no' => 'required|unique:treasury_adjustments,adjustment_no,' . $adjustment->id,
            'adjustment_date' => 'required|date',
            'adjustment_type' => 'required|in:SHORTAGE,OVERAGE,CORRECTION',
            'amount' => 'nullable|numeric',
            'reason' => 'nullable',
            'notes' => 'nullable',
        ]);

        $adjustment->update($data);
        return response()->json($adjustment);
    }

    public function destroy($id)
    {
        $adjustment = TreasuryAdjustment::findOrFail($id);
        $adjustment->delete();
        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $adjustment = TreasuryAdjustment::onlyTrashed()->findOrFail($id);
        $adjustment->restore();
        return response()->json($adjustment);
    }

    public function forceDelete($id)
    {
        $adjustment = TreasuryAdjustment::onlyTrashed()->findOrFail($id);
        $adjustment->forceDelete();
        return response()->json(null, 204);
    }
}
