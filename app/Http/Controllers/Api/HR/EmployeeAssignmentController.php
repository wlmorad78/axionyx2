<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeAssignment::with($with);

        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->job_title_id) $query->where('job_title_id', $request->job_title_id);
        if ($request->is_current !== null) $query->where('is_current', $request->boolean('is_current'));
        if ($request->trashed) $query->onlyTrashed();

        return $query->orderBy('effective_from', 'desc')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_assignment', 'store'));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeAssignment::where('employee_id', $data['employee_id'])
                ->where('is_current', true)->update(['is_current' => false]);
        }

        return response()->json(EmployeeAssignment::create($data), 201);
    }

    public function show(EmployeeAssignment $employeeAssignment)
    {
        return $employeeAssignment->load([
            'employee', 'branch', 'organizationUnit', 'costCenter', 'salesTerritory',
            'jobTitle', 'jobGrade', 'salaryScale', 'directManager',
        ]);
    }

    public function update(Request $request, EmployeeAssignment $employeeAssignment)
    {
        $data = $request->validate(ValidationRules::for('employee_assignment', 'update', $employeeAssignment));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeAssignment::where('employee_id', $employeeAssignment->employee_id)
                ->where('is_current', true)
                ->where('id', '!=', $employeeAssignment->id)
                ->update(['is_current' => false]);
        }

        $employeeAssignment->update($data);
        return response()->json($employeeAssignment);
    }

    public function destroy(EmployeeAssignment $employeeAssignment) { $employeeAssignment->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $a = EmployeeAssignment::onlyTrashed()->findOrFail($id); $a->restore(); return response()->json($a); }
    public function forceDelete(int $id) { EmployeeAssignment::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function schema() { return ValidationRules::for('employee_assignment', 'store'); }
}
