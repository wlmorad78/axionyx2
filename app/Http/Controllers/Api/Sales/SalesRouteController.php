<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Route as SalesRoute;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesRouteController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesRoute::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->sales_territory_id) {
            $query->where('sales_territory_id', $request->sales_territory_id);
        }
        if ($request->employee_id) {
            $routeIds = \App\Models\RouteSchedule::where('employee_id', $request->employee_id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->pluck('route_id');
            $query->whereIn('id', $routeIds);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route', 'store'));

        if (empty($data['code'])) {
            $data['code'] = $this->generateNextCode();
        }

        return response()->json(SalesRoute::create($data), 201);
    }

    public function nextCode(Request $request)
    {
        return response()->json(['code' => $this->generateNextCode()]);
    }

    public function show($id)
    {
        $sales_route = SalesRoute::withTrashed()->findOrFail($id);

        return $sales_route->load([
            'company',
            'branch',
            'salesTerritory',
            'schedules.employee',
            'customers.customer',
            'visits',
        ]);
    }

    public function update(Request $request, $id)
    {
        $sales_route = SalesRoute::withTrashed()->findOrFail($id);

        $data = $request->validate(ValidationRules::for('route', 'update', $sales_route));

        $sales_route->update($data);

        return response()->json($sales_route);
    }

    public function destroy($id)
    {
        $sales_route = SalesRoute::withTrashed()->findOrFail($id);

        if ($sales_route->trashed()) {
            $sales_route->forceDelete();
        } else {
            $sales_route->delete();
        }

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesRoute::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesRoute::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('route', 'store');
    }

    protected function generateNextCode(): string
    {
        $last = SalesRoute::withTrashed()->where('code', 'like', 'RT-%')
            ->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")
            ->first();

        if ($last) {
            $num = (int) substr($last->code, 3) + 1;
        } else {
            $num = 1;
        }

        return 'RT-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
