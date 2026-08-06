<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RouteVisit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteVisitController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = RouteVisit::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->visit_status) {
            $query->where('visit_status', $request->visit_status);
        }

        if ($request->visit_date) {
            $query->where('visit_date', $request->visit_date);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_visit', 'store'));
        return response()->json(RouteVisit::create($data), 201);
    }

    public function show(RouteVisit $routeVisit)
    {
        return $routeVisit->load(['company', 'branch', 'route', 'salesRep', 'customer']);
    }

    public function update(Request $request, RouteVisit $routeVisit)
    {
        $data = $request->validate(ValidationRules::for('route_visit', 'update', $routeVisit));
        $routeVisit->update($data);
        return response()->json($routeVisit);
    }

    public function destroy(RouteVisit $routeVisit)
    {
        $routeVisit->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = RouteVisit::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        RouteVisit::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('route_visit', 'store');
    }
}
