<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\TreasuryShift;
use Illuminate\Http\Request;

class TreasuryShiftController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['treasury', 'cashier'];
        $query = TreasuryShift::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->treasury_id) {
            $query->where('treasury_id', $request->treasury_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->cashier_id) {
            $query->where('cashier_id', $request->cashier_id);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
            'treasury_id' => 'required',
            'shift_no' => 'required|unique:treasury_shifts,shift_no',
            'cashier_id' => 'nullable',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'opening_balance' => 'nullable|numeric',
            'closing_balance' => 'nullable|numeric',
            'actual_balance' => 'nullable|numeric',
            'difference_amount' => 'nullable|numeric',
            'status' => 'required|in:OPEN,PENDING_APPROVAL,CLOSED,CANCELLED',
        ]);

        $treasuryShift = TreasuryShift::create($data);
        return response()->json($treasuryShift, 201);
    }

    public function show($id)
    {
        $treasuryShift = TreasuryShift::with(['treasury', 'cashier', 'transactions'])->findOrFail($id);
        return response()->json($treasuryShift);
    }

    public function update(Request $request, $id)
    {
        $treasuryShift = TreasuryShift::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
            'treasury_id' => 'required',
            'shift_no' => 'required|unique:treasury_shifts,shift_no,' . $treasuryShift->id,
            'cashier_id' => 'nullable',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'opening_balance' => 'nullable|numeric',
            'closing_balance' => 'nullable|numeric',
            'actual_balance' => 'nullable|numeric',
            'difference_amount' => 'nullable|numeric',
            'status' => 'required|in:OPEN,PENDING_APPROVAL,CLOSED,CANCELLED',
        ]);

        $treasuryShift->update($data);
        return response()->json($treasuryShift);
    }

    public function destroy($id)
    {
        $treasuryShift = TreasuryShift::findOrFail($id);
        $treasuryShift->delete();
        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $treasuryShift = TreasuryShift::onlyTrashed()->findOrFail($id);
        $treasuryShift->restore();
        return response()->json($treasuryShift);
    }

    public function forceDelete($id)
    {
        $treasuryShift = TreasuryShift::onlyTrashed()->findOrFail($id);
        $treasuryShift->forceDelete();
        return response()->json(null, 204);
    }
}
