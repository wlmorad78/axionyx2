<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    protected SuperAdminService $superAdmin;

    public function __construct(SuperAdminService $superAdmin)
    {
        $this->superAdmin = $superAdmin;
    }

    /**
     * GET /api/super-admin/stats — Platform overview
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->superAdmin->getPlatformStats());
    }

    /**
     * GET /api/super-admin/health — Platform health
     */
    public function health(): JsonResponse
    {
        return response()->json($this->superAdmin->getHealth());
    }

    /**
     * GET /api/super-admin/companies — List all companies
     */
    public function companies(Request $request): JsonResponse
    {
        $result = $this->superAdmin->listCompanies($request->only([
            'search', 'plan_id', 'is_active', 'per_page',
        ]));
        return response()->json($result);
    }

    /**
     * GET /api/super-admin/companies/{company} — Company details
     */
    public function companyShow(int $company): JsonResponse
    {
        $result = $this->superAdmin->getCompany($company);
        if (!$result) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        return response()->json($result);
    }

    /**
     * PUT /api/super-admin/companies/{company}/subscription — Update subscription
     */
    public function updateSubscription(Request $request, int $company): JsonResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'sometimes|exists:subscription_plans,id',
            'status' => 'sometimes|in:active,expired,suspended',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'amount' => 'sometimes|numeric|min:0',
        ]);

        $subscription = $this->superAdmin->updateCompanySubscription($company, $validated);
        return response()->json(['data' => $subscription]);
    }

    /**
     * GET /api/super-admin/plans — List all plans with features
     */
    public function plans(): JsonResponse
    {
        return response()->json(['data' => $this->superAdmin->getPlans()]);
    }
}
