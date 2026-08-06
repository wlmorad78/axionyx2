<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreement;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreement::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->agreement_type_id) {
            $query->where('agreement_type_id', $request->agreement_type_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('agreement_no', 'like', "%$s%")
                    ->orWhere('status', 'like', "%$s%")
                    ->orWhere('customer_id', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement', 'store'));
        return response()->json(CustomerAgreement::create($data), 201);
    }

    public function show(CustomerAgreement $customerAgreement)
    {
        return $customerAgreement->load([
            'company', 'agreementType', 'customer', 'createdBy', 'approvedBy',
            'items', 'marketingSupports', 'rebateRules', 'targets', 'payments', 'history',
        ]);
    }

    public function update(Request $request, CustomerAgreement $customerAgreement)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement', 'update', $customerAgreement));
        $customerAgreement->update($data);
        return response()->json($customerAgreement);
    }

    public function destroy(CustomerAgreement $customerAgreement)
    {
        $customerAgreement->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAgreement::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAgreement::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_agreement', 'store');
    }
}
