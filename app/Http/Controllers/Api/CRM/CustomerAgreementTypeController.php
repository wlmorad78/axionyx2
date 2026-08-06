<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%$s%")
                    ->orWhere('name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_type', 'store'));
        return response()->json(CustomerAgreementType::create($data), 201);
    }

    public function show(CustomerAgreementType $customerAgreementType)
    {
        return $customerAgreementType->load(['agreements']);
    }

    public function update(Request $request, CustomerAgreementType $customerAgreementType)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_type', 'update', $customerAgreementType));
        $customerAgreementType->update($data);
        return response()->json($customerAgreementType);
    }

    public function destroy(CustomerAgreementType $customerAgreementType)
    {
        $customerAgreementType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAgreementType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAgreementType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_agreement_type', 'store');
    }
}
