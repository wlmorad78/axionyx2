<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\CustomerVisit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerVisitController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerVisit::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->visit_date) {
            $query->whereDate('visit_date', $request->visit_date);
        }
        if ($request->visit_status) {
            $query->where('visit_status', $request->visit_status);
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
        $data = $request->validate(ValidationRules::for('customer_visit', 'store'));

        return response()->json(CustomerVisit::create($data), 201);
    }

    public function show(CustomerVisit $customer_visit)
    {
        return $customer_visit->load([
            'route',
            'employee',
            'customer',
        ]);
    }

    public function update(Request $request, CustomerVisit $customer_visit)
    {
        $data = $request->validate(ValidationRules::for('customer_visit', 'update', $customer_visit));

        $customer_visit->update($data);

        return response()->json($customer_visit);
    }

    public function destroy(CustomerVisit $customer_visit)
    {
        $customer_visit->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerVisit::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerVisit::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_visit', 'store');
    }
}
