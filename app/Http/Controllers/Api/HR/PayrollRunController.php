<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PayrollRun::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->payroll_period_id) {
            $query->where('payroll_period_id', $request->payroll_period_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payroll_run', 'store'));

        return response()->json(PayrollRun::create($data), 201);
    }

    public function show(PayrollRun $payrollRun)
    {
        return $payrollRun->load(['company', 'payrollPeriod', 'creator', 'details']);
    }

    public function update(Request $request, PayrollRun $payrollRun)
    {
        $data = $request->validate(ValidationRules::for('payroll_run', 'update', $payrollRun));

        $payrollRun->update($data);

        return response()->json($payrollRun);
    }

    public function destroy(PayrollRun $payrollRun)
    {
        $payrollRun->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $payrollRun = PayrollRun::onlyTrashed()->findOrFail($id);

        $payrollRun->restore();

        return response()->json($payrollRun);
    }

    public function forceDelete(int $id)
    {
        PayrollRun::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('payroll_run', 'store');
    }
}
