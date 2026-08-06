<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMission;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeMissionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeMission::with($with);

        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->status) $query->where('status', $request->status);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('destination', 'like', "%$s%")->orWhere('reason', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_mission', 'store'));

        return response()->json(EmployeeMission::create($data), 201);
    }

    public function show(EmployeeMission $employeeMission)
    {
        return $employeeMission->load(['employee', 'approver']);
    }

    public function update(Request $request, EmployeeMission $employeeMission)
    {
        $data = $request->validate(ValidationRules::for('employee_mission', 'update', $employeeMission));

        $employeeMission->update($data);

        return response()->json($employeeMission);
    }

    public function destroy(EmployeeMission $employeeMission)
    {
        $employeeMission->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeMission = EmployeeMission::onlyTrashed()->findOrFail($id);
        $employeeMission->restore();

        return response()->json($employeeMission);
    }

    public function forceDelete(int $id)
    {
        EmployeeMission::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('employee_mission', 'store');
    }
}
