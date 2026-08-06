<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RoutePoint;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RoutePointController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['route'];
        $query = RoutePoint::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderBy('sequence_no')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_point', 'store'));

        return response()->json(RoutePoint::create($data), 201);
    }

    public function show(RoutePoint $route_point)
    {
        return $route_point->load(['route']);
    }

    public function update(Request $request, RoutePoint $route_point)
    {
        $data = $request->validate(ValidationRules::for('route_point', 'update', $route_point));

        $route_point->update($data);

        return response()->json($route_point);
    }

    public function destroy(RoutePoint $route_point)
    {
        $route_point->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = RoutePoint::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        RoutePoint::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('route_point', 'store');
    }
}
