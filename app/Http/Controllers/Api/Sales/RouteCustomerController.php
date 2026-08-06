<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RouteCustomer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteCustomerController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = RouteCustomer::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('customer', function ($q2) use ($s) {
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
        $data = $request->validate(ValidationRules::for('route_customer', 'store'));
        unset($data['days'], $data['day_of_week']);

        return response()->json(RouteCustomer::create($data), 201);
    }

    public function show(RouteCustomer $route_customer)
    {
        return $route_customer->load([
            'route',
            'customer',
        ]);
    }

    public function update(Request $request, RouteCustomer $route_customer)
    {
        $data = $request->validate(ValidationRules::for('route_customer', 'update', $route_customer));
        unset($data['days'], $data['day_of_week']);

        $route_customer->update($data);

        return response()->json($route_customer);
    }

    public function destroy(RouteCustomer $route_customer)
    {
        $route_customer->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = RouteCustomer::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        RouteCustomer::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('route_customer', 'store');
    }
}
