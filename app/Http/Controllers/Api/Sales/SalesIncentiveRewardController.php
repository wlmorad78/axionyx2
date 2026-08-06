<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentiveReward;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveRewardController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentiveReward::with($with);

        if ($request->sales_incentive_id) {
            $query->where('sales_incentive_id', $request->sales_incentive_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reward_type', 'like', "%$s%")
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
        $data = $request->validate(ValidationRules::for('sales_incentive_reward', 'store'));
        return response()->json(SalesIncentiveReward::create($data), 201);
    }

    public function show(SalesIncentiveReward $salesIncentiveReward)
    {
        return $salesIncentiveReward->load(['salesIncentive']);
    }

    public function update(Request $request, SalesIncentiveReward $salesIncentiveReward)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive_reward', 'update', $salesIncentiveReward));
        $salesIncentiveReward->update($data);
        return response()->json($salesIncentiveReward);
    }

    public function destroy(SalesIncentiveReward $salesIncentiveReward)
    {
        $salesIncentiveReward->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesIncentiveReward::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesIncentiveReward::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_incentive_reward', 'store');
    }
}
