<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementTarget;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementTargetController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementTarget::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('achievement_percent', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_target', 'store'));
        return response()->json(CustomerAgreementTarget::create($data), 201);
    }

    public function show(CustomerAgreementTarget $customerAgreementTarget)
    {
        return $customerAgreementTarget->load(['customerAgreement']);
    }

    public function update(Request $request, CustomerAgreementTarget $customerAgreementTarget)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_target', 'update', $customerAgreementTarget));
        $customerAgreementTarget->update($data);
        return response()->json($customerAgreementTarget);
    }

    public function destroy(CustomerAgreementTarget $customerAgreementTarget)
    {
        $customerAgreementTarget->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAgreementTarget::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAgreementTarget::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_agreement_target', 'store');
    }
}
