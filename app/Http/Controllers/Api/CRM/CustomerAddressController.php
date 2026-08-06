<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAddress::with($with);

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_address', 'store'));
        return response()->json(CustomerAddress::create($data), 201);
    }

    public function show(CustomerAddress $customerAddress)
    {
        return $customerAddress->load(['customer', 'country', 'governorate', 'city', 'area']);
    }

    public function update(Request $request, CustomerAddress $customerAddress)
    {
        $data = $request->validate(ValidationRules::for('customer_address', 'update', $customerAddress));
        $customerAddress->update($data);
        return response()->json($customerAddress);
    }

    public function destroy(CustomerAddress $customerAddress)
    {
        $customerAddress->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAddress::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAddress::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_address', 'store');
    }
}
