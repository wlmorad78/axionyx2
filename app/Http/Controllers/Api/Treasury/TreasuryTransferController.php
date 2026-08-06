<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryTransfer;
use Illuminate\Http\Request;

class TreasuryTransferController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['fromTreasury', 'toTreasury'];
        $query = TreasuryTransfer::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->from_treasury_id) {
            $query->where('from_treasury_id', $request->from_treasury_id);
        }
        if ($request->to_treasury_id) {
            $query->where('to_treasury_id', $request->to_treasury_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'from_treasury_id' => 'required',
            'to_treasury_id' => 'required',
            'transfer_no' => 'required|unique:treasury_transfers,transfer_no',
            'transfer_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'notes' => 'nullable',
            'status' => 'nullable',
        ]);

        $transfer = TreasuryTransfer::create($data);
        return response()->json($transfer, 201);
    }

    public function show($id)
    {
        $transfer = TreasuryTransfer::with(['fromTreasury', 'toTreasury'])->findOrFail($id);
        return response()->json($transfer);
    }

    public function update(Request $request, $id)
    {
        $transfer = TreasuryTransfer::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'required',
            'from_treasury_id' => 'required',
            'to_treasury_id' => 'required',
            'transfer_no' => 'required|unique:treasury_transfers,transfer_no,' . $transfer->id,
            'transfer_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'notes' => 'nullable',
            'status' => 'nullable',
        ]);

        $transfer->update($data);
        return response()->json($transfer);
    }

    public function destroy($id)
    {
        $transfer = TreasuryTransfer::findOrFail($id);
        $transfer->delete();
        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $transfer = TreasuryTransfer::onlyTrashed()->findOrFail($id);
        $transfer->restore();
        return response()->json($transfer);
    }

    public function forceDelete($id)
    {
        $transfer = TreasuryTransfer::onlyTrashed()->findOrFail($id);
        $transfer->forceDelete();
        return response()->json(null, 204);
    }
}
