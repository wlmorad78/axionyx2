<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeReward;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeRewardController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeReward::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reason', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_reward', 'store'));

        return response()->json(EmployeeReward::create($data), 201);
    }

    public function show(EmployeeReward $employeeReward)
    {
        return $employeeReward->load(['employee']);
    }

    public function update(Request $request, EmployeeReward $employeeReward)
    {
        $data = $request->validate(ValidationRules::for('employee_reward', 'update', $employeeReward));

        $employeeReward->update($data);

        return response()->json($employeeReward);
    }

    public function destroy(EmployeeReward $employeeReward)
    {
        $employeeReward->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeReward = EmployeeReward::onlyTrashed()->findOrFail($id);
        $employeeReward->restore();

        return response()->json($employeeReward);
    }

    public function forceDelete(int $id)
    {
        EmployeeReward::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('employee_reward', 'store');
    }
}
