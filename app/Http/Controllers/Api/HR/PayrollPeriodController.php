<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PayrollPeriodController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PayrollPeriod::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('period_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payroll_period', 'store'));

        return response()->json(PayrollPeriod::create($data), 201);
    }

    public function show(PayrollPeriod $payrollPeriod)
    {
        return $payrollPeriod->load(['company', 'payrollRuns']);
    }

    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        $data = $request->validate(ValidationRules::for('payroll_period', 'update', $payrollPeriod));

        $payrollPeriod->update($data);

        return response()->json($payrollPeriod);
    }

    public function destroy(PayrollPeriod $payrollPeriod)
    {
        $payrollPeriod->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $payrollPeriod = PayrollPeriod::onlyTrashed()->findOrFail($id);

        $payrollPeriod->restore();

        return response()->json($payrollPeriod);
    }

    public function forceDelete(int $id)
    {
        PayrollPeriod::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('payroll_period', 'store');
    }
}
