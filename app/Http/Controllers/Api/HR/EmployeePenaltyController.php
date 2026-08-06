<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeePenalty;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeePenaltyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeePenalty::with($with);

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
        $data = $request->validate(ValidationRules::for('employee_penalty', 'store'));

        return response()->json(EmployeePenalty::create($data), 201);
    }

    public function show(EmployeePenalty $employeePenalty)
    {
        return $employeePenalty->load(['employee']);
    }

    public function update(Request $request, EmployeePenalty $employeePenalty)
    {
        $data = $request->validate(ValidationRules::for('employee_penalty', 'update', $employeePenalty));

        $employeePenalty->update($data);

        return response()->json($employeePenalty);
    }

    public function destroy(EmployeePenalty $employeePenalty)
    {
        $employeePenalty->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeePenalty = EmployeePenalty::onlyTrashed()->findOrFail($id);
        $employeePenalty->restore();

        return response()->json($employeePenalty);
    }

    public function forceDelete(int $id)
    {
        EmployeePenalty::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('employee_penalty', 'store');
    }
}
