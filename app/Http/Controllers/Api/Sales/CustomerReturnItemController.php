<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerReturnItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerReturnItemController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerReturnItem::with($with);

        if ($request->customer_return_id) {
            $query->where('customer_return_id', $request->customer_return_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_return_item', 'store'));
        return response()->json(CustomerReturnItem::create($data), 201);
    }

    public function show(CustomerReturnItem $customerReturnItem)
    {
        return $customerReturnItem->load(['item', 'unit']);
    }

    public function update(Request $request, CustomerReturnItem $customerReturnItem)
    {
        $data = $request->validate(ValidationRules::for('customer_return_item', 'update', $customerReturnItem));
        $customerReturnItem->update($data);
        return response()->json($customerReturnItem);
    }

    public function destroy(CustomerReturnItem $customerReturnItem)
    {
        $customerReturnItem->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerReturnItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        CustomerReturnItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_return_item', 'store');
    }
}
