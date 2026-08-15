<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesmanAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $defaultWith = ['employee.user', 'salesTerritory', 'warehouse', 'treasury'];
        $mergedWith = array_unique(array_merge($defaultWith, $with));
        $query = SalesmanAssignment::with($mergedWith);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->sales_territory_id) {
            $query->where('sales_territory_id', $request->sales_territory_id);
        }
        if ($request->job_role) {
            $query->where('job_role', $request->job_role);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('employee', function ($q2) use ($s) {
                    $q2->where('name', 'like', "%$s%");
                });
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salesman_assignment', 'store'));

        $assignment = SalesmanAssignment::create($data);

        return response()->json($assignment->load(['employee.user', 'salesTerritory', 'warehouse', 'treasury']), 201);
    }

    public function show(SalesmanAssignment $salesman_assignment)
    {
        return $salesman_assignment->load([
            'employee.user',
            'salesTerritory',
            'warehouse',
            'treasury',
            'parentAssignment',
            'children.employee.user',
        ]);
    }

    public function update(Request $request, SalesmanAssignment $salesman_assignment)
    {
        $data = $request->validate(ValidationRules::for('salesman_assignment', 'update', $salesman_assignment));

        $salesman_assignment->update($data);

        return response()->json($salesman_assignment->load(['employee.user', 'salesTerritory', 'warehouse', 'treasury']));
    }

    public function destroy(SalesmanAssignment $salesman_assignment)
    {
        $salesman_assignment->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesmanAssignment::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesmanAssignment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('salesman_assignment', 'store');
    }
}
