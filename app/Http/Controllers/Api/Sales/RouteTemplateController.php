<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{RouteTemplate};
use App\Support\ValidationRules;

class RouteTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = RouteTemplate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('route_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_template', 'create'));
        $routeTemplate = RouteTemplate::create($data);
        return response()->json($routeTemplate, 201);
    }

    public function show($id)
    {
        return RouteTemplate::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $routeTemplate = RouteTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('route_template', 'update', $routeTemplate));
        $routeTemplate->update($data);
        return $routeTemplate;
    }

    public function destroy($id)
    {
        $routeTemplate = RouteTemplate::findOrFail($id);
        $routeTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $routeTemplate = RouteTemplate::withTrashed()->findOrFail($id);
        $routeTemplate->restore();
        return $routeTemplate;
    }

    public function forceDelete($id)
    {
        $routeTemplate = RouteTemplate::withTrashed()->findOrFail($id);
        $routeTemplate->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
