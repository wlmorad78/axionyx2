<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Settings\CompanySubscription;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CompanySubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompanySubscription::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('company_subscription', 'store'));
        $subscription = CompanySubscription::create($data);

        return response()->json($subscription, 201);
    }

    public function show(CompanySubscription $companySubscription)
    {
        return $companySubscription;
    }

    public function update(Request $request, CompanySubscription $companySubscription)
    {
        $data = $request->validate(ValidationRules::for('company_subscription', 'update', $companySubscription));
        $companySubscription->update($data);

        return response()->json($companySubscription);
    }

    public function destroy(CompanySubscription $companySubscription)
    {
        $companySubscription->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $subscription = CompanySubscription::onlyTrashed()->findOrFail($id);
        $subscription->restore();

        return response()->json($subscription);
    }

    public function forceDelete(int $id)
    {
        $subscription = CompanySubscription::onlyTrashed()->findOrFail($id);
        $subscription->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('company_subscription', 'store');
    }
}
