<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementHistory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementHistoryController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementHistory::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('action_type', 'like', "%$s%");
            });
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_history', 'store'));
        return response()->json(CustomerAgreementHistory::create($data), 201);
    }

    public function show(CustomerAgreementHistory $customerAgreementHistory)
    {
        return $customerAgreementHistory->load(['customerAgreement', 'actionBy']);
    }

    public function update(Request $request, CustomerAgreementHistory $customerAgreementHistory)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_history', 'update', $customerAgreementHistory));
        $customerAgreementHistory->update($data);
        return response()->json($customerAgreementHistory);
    }

    public function destroy(CustomerAgreementHistory $customerAgreementHistory)
    {
        $customerAgreementHistory->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAgreementHistory::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAgreementHistory::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_agreement_history', 'store');
    }
}
