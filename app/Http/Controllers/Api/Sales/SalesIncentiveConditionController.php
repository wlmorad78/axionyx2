<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentiveCondition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveConditionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentiveCondition::with($with);

        if ($request->sales_incentive_id) {
            $query->where('sales_incentive_id', $request->sales_incentive_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('condition_type', 'like', "%$s%")
                    ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition', 'store'));
        return response()->json(SalesIncentiveCondition::create($data), 201);
    }

    public function show(SalesIncentiveCondition $salesIncentiveCondition)
    {
        return $salesIncentiveCondition->load(['salesIncentive']);
    }

    public function update(Request $request, SalesIncentiveCondition $salesIncentiveCondition)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_condition', 'update', $salesIncentiveCondition));
        $salesIncentiveCondition->update($data);
        return response()->json($salesIncentiveCondition);
    }

    public function destroy(SalesIncentiveCondition $salesIncentiveCondition)
    {
        $salesIncentiveCondition->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesIncentiveCondition::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesIncentiveCondition::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_incentive_condition', 'store');
    }
}
