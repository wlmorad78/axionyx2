<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Collection::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('collection_no', 'like', "%$s%");
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('collection', 'store'));
        return response()->json(Collection::create($data), 201);
    }

    public function show(Collection $collection)
    {
        return $collection->load(['company', 'branch', 'salesRep', 'customer', 'salesInvoice', 'paymentMethod']);
    }

    public function update(Request $request, Collection $collection)
    {
        $data = $request->validate(ValidationRules::for('collection', 'update', $collection));
        $collection->update($data);
        return response()->json($collection);
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = Collection::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        Collection::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('collection', 'store');
    }
}
