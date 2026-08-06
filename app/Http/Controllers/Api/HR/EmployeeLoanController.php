<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLoan;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeLoanController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeLoan::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_loan', 'store'));

        return response()->json(EmployeeLoan::create($data), 201);
    }

    public function show(EmployeeLoan $employeeLoan)
    {
        return $employeeLoan->load(['employee']);
    }

    public function update(Request $request, EmployeeLoan $employeeLoan)
    {
        $data = $request->validate(ValidationRules::for('employee_loan', 'update', $employeeLoan));

        $employeeLoan->update($data);

        return response()->json($employeeLoan);
    }

    public function destroy(EmployeeLoan $employeeLoan)
    {
        $employeeLoan->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeLoan = EmployeeLoan::onlyTrashed()->findOrFail($id);
        $employeeLoan->restore();

        return response()->json($employeeLoan);
    }

    public function forceDelete(int $id)
    {
        EmployeeLoan::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $last = EmployeeLoan::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/LN-(\d+)/', $last->loan_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'LN-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema()
    {
        return ValidationRules::for('employee_loan', 'store');
    }
}
