<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Settings\CompanySubscriptionLimit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CompanySubscriptionLimitController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompanySubscriptionLimit::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('company_subscription_limit', 'store'));
        $limit = CompanySubscriptionLimit::create($data);

        return response()->json($limit, 201);
    }

    public function show(CompanySubscriptionLimit $companySubscriptionLimit)
    {
        return $companySubscriptionLimit;
    }

    public function update(Request $request, CompanySubscriptionLimit $companySubscriptionLimit)
    {
        $data = $request->validate(ValidationRules::for('company_subscription_limit', 'update', $companySubscriptionLimit));
        $companySubscriptionLimit->update($data);

        return response()->json($companySubscriptionLimit);
    }

    public function destroy(CompanySubscriptionLimit $companySubscriptionLimit)
    {
        $companySubscriptionLimit->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $limit = CompanySubscriptionLimit::onlyTrashed()->findOrFail($id);
        $limit->restore();

        return response()->json($limit);
    }

    public function forceDelete(int $id)
    {
        $limit = CompanySubscriptionLimit::onlyTrashed()->findOrFail($id);
        $limit->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('company_subscription_limit', 'store');
    }
}
