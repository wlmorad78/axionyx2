<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCountDetail;
use Illuminate\Http\Request;

class TreasuryCountDetailController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['count'];
        $query = TreasuryCountDetail::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_count_id) {
            $query->where('treasury_count_id', $request->treasury_count_id);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_count_id' => 'required',
            'denomination' => 'required',
            'qty' => 'required|integer',
            'total_amount' => 'nullable|numeric',
        ]);

        $detail = TreasuryCountDetail::create($data);
        return response()->json($detail, 201);
    }

    public function show($id)
    {
        $detail = TreasuryCountDetail::with(['count'])->findOrFail($id);
        return response()->json($detail);
    }

    public function update(Request $request, $id)
    {
        $detail = TreasuryCountDetail::findOrFail($id);

        $data = $request->validate([
            'treasury_count_id' => 'required',
            'denomination' => 'required',
            'qty' => 'required|integer',
            'total_amount' => 'nullable|numeric',
        ]);

        $detail->update($data);
        return response()->json($detail);
    }

    public function destroy($id)
    {
        $detail = TreasuryCountDetail::findOrFail($id);
        $detail->delete();
        return response()->json(null, 204);
    }

    public function restore($id)
    {
        $detail = TreasuryCountDetail::onlyTrashed()->findOrFail($id);
        $detail->restore();
        return response()->json($detail);
    }

    public function forceDelete($id)
    {
        $detail = TreasuryCountDetail::onlyTrashed()->findOrFail($id);
        $detail->forceDelete();
        return response()->json(null, 204);
    }
}
