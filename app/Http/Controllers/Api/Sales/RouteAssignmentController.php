<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RouteAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['route', 'vehicle', 'driver', 'assistant'];
        $query = RouteAssignment::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->assignment_date) {
            $query->where('assignment_date', $request->assignment_date);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_assignment', 'store'));

        return response()->json(RouteAssignment::create($data), 201);
    }

    public function show(RouteAssignment $route_assignment)
    {
        return $route_assignment->load(['route', 'vehicle', 'driver', 'assistant']);
    }

    public function update(Request $request, RouteAssignment $route_assignment)
    {
        $data = $request->validate(ValidationRules::for('route_assignment', 'update', $route_assignment));

        $route_assignment->update($data);

        return response()->json($route_assignment);
    }

    public function destroy(RouteAssignment $route_assignment)
    {
        $route_assignment->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = RouteAssignment::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        RouteAssignment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('route_assignment', 'store');
    }
}
