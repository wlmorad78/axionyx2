<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerMarketingSupport;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerMarketingSupportController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerMarketingSupport::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('support_value', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_marketing_support', 'store'));
        return response()->json(CustomerMarketingSupport::create($data), 201);
    }

    public function show(CustomerMarketingSupport $customerMarketingSupport)
    {
        return $customerMarketingSupport->load(['customerAgreement', 'marketingSupportType']);
    }

    public function update(Request $request, CustomerMarketingSupport $customerMarketingSupport)
    {
        $data = $request->validate(ValidationRules::for('customer_marketing_support', 'update', $customerMarketingSupport));
        $customerMarketingSupport->update($data);
        return response()->json($customerMarketingSupport);
    }

    public function destroy(CustomerMarketingSupport $customerMarketingSupport)
    {
        $customerMarketingSupport->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerMarketingSupport::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerMarketingSupport::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_marketing_support', 'store');
    }
}
