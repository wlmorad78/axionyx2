<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAdvance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class EmployeeAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = EmployeeAdvance::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('advance_number', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee_advance', 'store'));

        return response()->json(EmployeeAdvance::create($data), 201);
    }

    public function show(EmployeeAdvance $employeeAdvance)
    {
        return $employeeAdvance->load(['employee']);
    }

    public function update(Request $request, EmployeeAdvance $employeeAdvance)
    {
        $data = $request->validate(ValidationRules::for('employee_advance', 'update', $employeeAdvance));

        $employeeAdvance->update($data);

        return response()->json($employeeAdvance);
    }

    public function destroy(EmployeeAdvance $employeeAdvance)
    {
        $employeeAdvance->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $employeeAdvance = EmployeeAdvance::onlyTrashed()->findOrFail($id);
        $employeeAdvance->restore();

        return response()->json($employeeAdvance);
    }

    public function forceDelete(int $id)
    {
        EmployeeAdvance::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $last = EmployeeAdvance::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/ADV-(\d+)/', $last->advance_number, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'ADV-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema()
    {
        return ValidationRules::for('employee_advance', 'store');
    }
}
