<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeContractAmendment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeContractAmendmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeContractAmendment::with($with);

        if ($request->employee_contract_id) {
            $query->where('employee_contract_id', $request->employee_contract_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('amendment_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_contract_amendment', 'store'));

        return response()->json(EmployeeContractAmendment::create($data), 201);
    }

    public function show(EmployeeContractAmendment $employeeContractAmendment)
    {
        return $employeeContractAmendment->load(['contract']);
    }

    public function update(Request $request, EmployeeContractAmendment $employeeContractAmendment)
    {
        $data = $request->validate(ValidationRules::for('employee_contract_amendment', 'update', $employeeContractAmendment));

        $employeeContractAmendment->update($data);

        return response()->json($employeeContractAmendment);
    }

    public function destroy(EmployeeContractAmendment $employeeContractAmendment)
    {
        $employeeContractAmendment->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeContractAmendment = EmployeeContractAmendment::onlyTrashed()->findOrFail($id);
        $employeeContractAmendment->restore();

        return response()->json($employeeContractAmendment);
    }

    public function forceDelete(int $id)
    {
        EmployeeContractAmendment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $last = EmployeeContractAmendment::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/AMD-(\d+)/', $last->amendment_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'AMD-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema()
    {
        return ValidationRules::for('employee_contract_amendment', 'store');
    }
}
