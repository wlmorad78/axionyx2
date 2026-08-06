<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesmanSettlement;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanSettlementController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesmanSettlement::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('settlement_no', 'like', "%$s%");
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salesman_settlement', 'store'));
        return response()->json(SalesmanSettlement::create($data), 201);
    }

    public function show(SalesmanSettlement $salesmanSettlement)
    {
        return $salesmanSettlement->load(['company', 'branch', 'salesRep', 'route', 'loadRequest', 'issueOrder']);
    }

    public function update(Request $request, SalesmanSettlement $salesmanSettlement)
    {
        $data = $request->validate(ValidationRules::for('salesman_settlement', 'update', $salesmanSettlement));
        $salesmanSettlement->update($data);
        return response()->json($salesmanSettlement);
    }

    public function destroy(SalesmanSettlement $salesmanSettlement)
    {
        $salesmanSettlement->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesmanSettlement::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesmanSettlement::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('salesman_settlement', 'store');
    }
}
