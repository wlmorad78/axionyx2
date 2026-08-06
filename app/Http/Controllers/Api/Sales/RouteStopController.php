<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{RouteStop};
use App\Support\ValidationRules;

class RouteStopController extends Controller
{
    public function index(Request $request)
    {
        $query = RouteStop::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('sequence_no', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_stop', 'create'));
        $routeStop = RouteStop::create($data);
        return response()->json($routeStop, 201);
    }

    public function show($id)
    {
        return RouteStop::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $routeStop = RouteStop::findOrFail($id);
        $data = $request->validate(ValidationRules::for('route_stop', 'update', $routeStop));
        $routeStop->update($data);
        return $routeStop;
    }

    public function destroy($id)
    {
        $routeStop = RouteStop::findOrFail($id);
        $routeStop->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $routeStop = RouteStop::withTrashed()->findOrFail($id);
        $routeStop->restore();
        return $routeStop;
    }

    public function forceDelete($id)
    {
        $routeStop = RouteStop::withTrashed()->findOrFail($id);
        $routeStop->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
