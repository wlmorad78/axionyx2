<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\CustomerRebateRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerRebateRuleController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerRebateRule::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rebate_percent', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_rebate_rule', 'store'));
        return response()->json(CustomerRebateRule::create($data), 201);
    }

    public function show(CustomerRebateRule $customerRebateRule)
    {
        return $customerRebateRule->load(['customerAgreement']);
    }

    public function update(Request $request, CustomerRebateRule $customerRebateRule)
    {
        $data = $request->validate(ValidationRules::for('customer_rebate_rule', 'update', $customerRebateRule));
        $customerRebateRule->update($data);
        return response()->json($customerRebateRule);
    }

    public function destroy(CustomerRebateRule $customerRebateRule)
    {
        $customerRebateRule->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerRebateRule::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerRebateRule::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_rebate_rule', 'store');
    }
}
