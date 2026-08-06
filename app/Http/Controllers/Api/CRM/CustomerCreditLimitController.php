<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerCreditLimit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerCreditLimitController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerCreditLimit::with($with);

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
        $data = $request->validate(ValidationRules::for('customer_credit_limit', 'store'));
        return response()->json(CustomerCreditLimit::create($data), 201);
    }

    public function show(CustomerCreditLimit $customerCreditLimit)
    {
        return $customerCreditLimit->load(['customer']);
    }

    public function update(Request $request, CustomerCreditLimit $customerCreditLimit)
    {
        $data = $request->validate(ValidationRules::for('customer_credit_limit', 'update', $customerCreditLimit));
        $customerCreditLimit->update($data);
        return response()->json($customerCreditLimit);
    }

    public function destroy(CustomerCreditLimit $customerCreditLimit)
    {
        $customerCreditLimit->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerCreditLimit::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerCreditLimit::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_credit_limit', 'store');
    }
}
