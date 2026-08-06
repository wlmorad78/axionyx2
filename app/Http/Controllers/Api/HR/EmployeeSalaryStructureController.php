<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalaryStructure;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeSalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeSalaryStructure::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->salary_component_id) {
            $query->where('salary_component_id', $request->salary_component_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_salary_structure', 'store'));

        if (isset($data['is_current']) && $data['is_current']) {
            EmployeeSalaryStructure::where('employee_id', $data['employee_id'])
                ->where('salary_component_id', $data['salary_component_id'])
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        return response()->json(EmployeeSalaryStructure::create($data), 201);
    }

    public function show(EmployeeSalaryStructure $employeeSalaryStructure)
    {
        return $employeeSalaryStructure->load(['employee', 'salaryComponent']);
    }

    public function update(Request $request, EmployeeSalaryStructure $employeeSalaryStructure)
    {
        $data = $request->validate(ValidationRules::for('employee_salary_structure', 'update', $employeeSalaryStructure));

        if (isset($data['is_current']) && $data['is_current']) {
            EmployeeSalaryStructure::where('employee_id', $employeeSalaryStructure->employee_id)
                ->where('salary_component_id', $employeeSalaryStructure->salary_component_id)
                ->where('is_current', true)
                ->where('id', '!=', $employeeSalaryStructure->id)
                ->update(['is_current' => false]);
        }

        $employeeSalaryStructure->update($data);

        return response()->json($employeeSalaryStructure);
    }

    public function destroy(EmployeeSalaryStructure $employeeSalaryStructure)
    {
        $employeeSalaryStructure->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeSalaryStructure = EmployeeSalaryStructure::onlyTrashed()->findOrFail($id);

        $employeeSalaryStructure->restore();

        return response()->json($employeeSalaryStructure);
    }

    public function forceDelete(int $id)
    {
        EmployeeSalaryStructure::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('employee_salary_structure', 'store');
    }
}
