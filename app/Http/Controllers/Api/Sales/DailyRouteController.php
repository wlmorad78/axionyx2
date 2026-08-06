<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DailyRoute;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DailyRouteController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['route', 'employee'];
        $query = DailyRoute::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->route_date) {
            $query->where('route_date', $request->route_date);
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
        $data = $request->validate(ValidationRules::for('daily_route', 'store'));

        return response()->json(DailyRoute::create($data), 201);
    }

    public function show(DailyRoute $daily_route)
    {
        return $daily_route->load([
            'route',
            'employee',
            'customers.customer',
            'events',
        ]);
    }

    public function update(Request $request, DailyRoute $daily_route)
    {
        $data = $request->validate(ValidationRules::for('daily_route', 'update', $daily_route));

        $daily_route->update($data);

        return response()->json($daily_route);
    }

    public function destroy(DailyRoute $daily_route)
    {
        $daily_route->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = DailyRoute::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        DailyRoute::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('daily_route', 'store');
    }
}
