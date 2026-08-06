<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerContact;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerContactController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerContact::with($with);

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_contact', 'store'));
        return response()->json(CustomerContact::create($data), 201);
    }

    public function show(CustomerContact $customerContact)
    {
        return $customerContact->load(['customer']);
    }

    public function update(Request $request, CustomerContact $customerContact)
    {
        $data = $request->validate(ValidationRules::for('customer_contact', 'update', $customerContact));
        $customerContact->update($data);
        return response()->json($customerContact);
    }

    public function destroy(CustomerContact $customerContact)
    {
        $customerContact->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerContact::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerContact::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_contact', 'store');
    }
}
