<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DailyRouteCustomer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DailyRouteCustomerController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['dailyRoute', 'customer'];
        $query = DailyRouteCustomer::with($with);

        if ($request->daily_route_id) {
            $query->where('daily_route_id', $request->daily_route_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->visit_status) {
            $query->where('visit_status', $request->visit_status);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderBy('visit_order')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('daily_route_customer', 'store'));

        return response()->json(DailyRouteCustomer::create($data), 201);
    }

    public function show(DailyRouteCustomer $daily_route_customer)
    {
        return $daily_route_customer->load(['dailyRoute', 'customer']);
    }

    public function update(Request $request, DailyRouteCustomer $daily_route_customer)
    {
        $data = $request->validate(ValidationRules::for('daily_route_customer', 'update', $daily_route_customer));

        $daily_route_customer->update($data);

        return response()->json($daily_route_customer);
    }

    public function destroy(DailyRouteCustomer $daily_route_customer)
    {
        $daily_route_customer->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = DailyRouteCustomer::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        DailyRouteCustomer::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('daily_route_customer', 'store');
    }
}
