<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAgreementItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerAgreementItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAgreementItem::with($with);

        if ($request->customer_agreement_id) {
            $query->where('customer_agreement_id', $request->customer_agreement_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('discount_type', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_item', 'store'));
        return response()->json(CustomerAgreementItem::create($data), 201);
    }

    public function show(CustomerAgreementItem $customerAgreementItem)
    {
        return $customerAgreementItem->load(['customerAgreement', 'item', 'brand', 'itemCategory']);
    }

    public function update(Request $request, CustomerAgreementItem $customerAgreementItem)
    {
        $data = $request->validate(ValidationRules::for('customer_agreement_item', 'update', $customerAgreementItem));
        $customerAgreementItem->update($data);
        return response()->json($customerAgreementItem);
    }

    public function destroy(CustomerAgreementItem $customerAgreementItem)
    {
        $customerAgreementItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerAgreementItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerAgreementItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_agreement_item', 'store');
    }
}
