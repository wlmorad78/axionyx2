<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesmanDebtPaymentLine;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanDebtPaymentLineController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesmanDebtPaymentLine::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->salesman_debt_id) {
            $query->where('salesman_debt_id', $request->salesman_debt_id);
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }

        if ($request->from_date) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('payment_date')->paginate($request->per_page ?? 15);
    }

    public function show(SalesmanDebtPaymentLine $salesmanDebtPaymentLine)
    {
        return $salesmanDebtPaymentLine->load([
            'salesmanDebt', 'salesmanAccount', 'salesman',
            'paymentMethod', 'treasury', 'collection',
            'receivedByEmployee', 'createdByEmployee',
        ]);
    }

    public function destroy(SalesmanDebtPaymentLine $salesmanDebtPaymentLine)
    {
        $salesmanDebtPaymentLine->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesmanDebtPaymentLine::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesmanDebtPaymentLine::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('salesman_debt_payment_line', 'store');
    }
}