<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerReturn;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerReturnController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerReturn::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('return_no', 'like', "%$s%");
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_return', 'store'));
        return response()->json(CustomerReturn::create($data), 201);
    }

    public function show(CustomerReturn $customerReturn)
    {
        return $customerReturn->load(['company', 'branch', 'warehouse', 'salesInvoice', 'customer', 'salesRep', 'route', 'items.item', 'items.unit']);
    }

    public function update(Request $request, CustomerReturn $customerReturn)
    {
        $data = $request->validate(ValidationRules::for('customer_return', 'update', $customerReturn));
        $customerReturn->update($data);
        return response()->json($customerReturn);
    }

    public function destroy(CustomerReturn $customerReturn)
    {
        $customerReturn->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerReturn::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerReturn::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_return', 'store');
    }
}
