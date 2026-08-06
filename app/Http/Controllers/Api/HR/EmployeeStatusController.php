<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeStatus;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeStatusController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeStatus::with($with);
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_status', 'store'));
        return response()->json(EmployeeStatus::create($data), 201);
    }

    public function show(EmployeeStatus $employeeStatus) { return $employeeStatus; }

    public function update(Request $request, EmployeeStatus $employeeStatus)
    {
        $data = $request->validate(ValidationRules::for('employee_status', 'update', $employeeStatus));
        $employeeStatus->update($data);
        return response()->json($employeeStatus);
    }

    public function destroy(EmployeeStatus $employeeStatus)
    {
        if ($employeeStatus->is_system) return response()->json(['message' => 'لا يمكن حذف حالة نظام'], 403);
        $employeeStatus->delete();
        return response()->json(null, 204);
    }

    public function schema() { return ValidationRules::for('employee_status', 'store'); }
}
