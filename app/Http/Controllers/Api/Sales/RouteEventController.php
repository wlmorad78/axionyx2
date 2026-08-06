<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RouteEvent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteEventController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['dailyRoute', 'customer'];
        $query = RouteEvent::with($with);

        if ($request->daily_route_id) {
            $query->where('daily_route_id', $request->daily_route_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->event_type) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_event', 'store'));

        return response()->json(RouteEvent::create($data), 201);
    }

    public function show(RouteEvent $route_event)
    {
        return $route_event->load(['dailyRoute', 'customer']);
    }

    public function update(Request $request, RouteEvent $route_event)
    {
        $data = $request->validate(ValidationRules::for('route_event', 'update', $route_event));

        $route_event->update($data);

        return response()->json($route_event);
    }

    public function destroy(RouteEvent $route_event)
    {
        $route_event->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = RouteEvent::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        RouteEvent::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('route_event', 'store');
    }
}
