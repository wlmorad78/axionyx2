<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeShift;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeShiftController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeShift::with($with);

        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->is_current !== null) $query->where('is_current', $request->boolean('is_current'));

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_shift', 'store'));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeShift::where('employee_id', $data['employee_id'])
                ->where('is_current', true)->update(['is_current' => false]);
        }

        return response()->json(EmployeeShift::create($data), 201);
    }

    public function show(EmployeeShift $employeeShift)
    {
        return $employeeShift->load(['employee', 'shift']);
    }

    public function update(Request $request, EmployeeShift $employeeShift)
    {
        $data = $request->validate(ValidationRules::for('employee_shift', 'update', $employeeShift));

        if (!empty($data['is_current']) && $data['is_current']) {
            EmployeeShift::where('employee_id', $employeeShift->employee_id)
                ->where('is_current', true)
                ->where('id', '!=', $employeeShift->id)
                ->update(['is_current' => false]);
        }

        $employeeShift->update($data);
        return response()->json($employeeShift);
    }

    public function destroy(EmployeeShift $employeeShift)
    {
        $employeeShift->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeShift = EmployeeShift::onlyTrashed()->findOrFail($id);
        $employeeShift->restore();

        return response()->json($employeeShift);
    }

    public function forceDelete(int $id)
    {
        EmployeeShift::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('employee_shift', 'store');
    }
}
