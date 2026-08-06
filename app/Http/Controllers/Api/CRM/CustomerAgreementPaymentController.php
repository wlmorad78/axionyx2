<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementPayment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementPaymentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementPayment::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('payment_type', 'like', "%$s%")
                    ->orWhere('amount', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_payment', 'store'));
        return response()->json(CustomerAgreementPayment::create($data), 201);
    }

    public function show(CustomerAgreementPayment $customerAgreementPayment)
    {
        return $customerAgreementPayment->load(['customerAgreement']);
    }

    public function update(Request $request, CustomerAgreementPayment $customerAgreementPayment)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_payment', 'update', $customerAgreementPayment));
        $customerAgreementPayment->update($data);
        return response()->json($customerAgreementPayment);
    }

    public function destroy(CustomerAgreementPayment $customerAgreementPayment)
    {
        $customerAgreementPayment->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAgreementPayment::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAgreementPayment::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_agreement_payment', 'store');
    }
}
