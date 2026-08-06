<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesIncentive;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesIncentiveController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesIncentive::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive', 'store'));
        return response()->json(SalesIncentive::create($data), 201);
    }

    public function show(SalesIncentive $salesIncentive)
    {
        return $salesIncentive->load(['company', 'conditions', 'rewards']);
    }

    public function update(Request $request, SalesIncentive $salesIncentive)
    {
        $data = $request->validate(ValidationRules::for('sales_incentive', 'update', $salesIncentive));
        $salesIncentive->update($data);
        return response()->json($salesIncentive);
    }

    public function destroy(SalesIncentive $salesIncentive)
    {
        $salesIncentive->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = SalesIncentive::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        SalesIncentive::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_incentive', 'store');
    }
}
