<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollRunDetail;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PayrollRunDetailController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PayrollRunDetail::with($with);

        if ($request->payroll_run_id) {
            $query->where('payroll_run_id', $request->payroll_run_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payroll_run_detail', 'store'));

        return response()->json(PayrollRunDetail::create($data), 201);
    }

    public function show(PayrollRunDetail $payrollRunDetail)
    {
        return $payrollRunDetail->load(['payrollRun', 'employee']);
    }

    public function update(Request $request, PayrollRunDetail $payrollRunDetail)
    {
        $data = $request->validate(ValidationRules::for('payroll_run_detail', 'update', $payrollRunDetail));

        $payrollRunDetail->update($data);

        return response()->json($payrollRunDetail);
    }

    public function destroy(PayrollRunDetail $payrollRunDetail)
    {
        $payrollRunDetail->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $payrollRunDetail = PayrollRunDetail::onlyTrashed()->findOrFail($id);

        $payrollRunDetail->restore();

        return response()->json($payrollRunDetail);
    }

    public function forceDelete(int $id)
    {
        PayrollRunDetail::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('payroll_run_detail', 'store');
    }
}
