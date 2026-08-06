<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\PlanLimit;
use App\Models\PlanPermission;
use App\Models\Settings\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionPlan::with(['modules', 'planPermissions', 'limits']);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:subscription_plans,code',
            'name' => 'required|string',
            'tier' => 'required|integer|min:1',
            'package_name' => 'nullable|string',
            'duration_months' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'monthly_price' => 'numeric|min:0',
            'setup_price' => 'numeric|min:0',
            'max_branches' => 'integer|min:1',
            'max_warehouses' => 'integer|min:1',
            'max_treasuries' => 'integer|min:1',
            'max_users' => 'integer|min:1',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'grace_period_days' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $plan = SubscriptionPlan::create($data);

        $this->syncPermissionsAndLimits($plan, $request);

        return response()->json($plan->load(['modules', 'planPermissions', 'limits']), 201);
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return $subscriptionPlan->load(['modules', 'planPermissions', 'limits']);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'tier' => 'sometimes|integer|min:1',
            'package_name' => 'nullable|string',
            'duration_months' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'monthly_price' => 'numeric|min:0',
            'setup_price' => 'numeric|min:0',
            'max_branches' => 'integer|min:1',
            'max_warehouses' => 'integer|min:1',
            'max_treasuries' => 'integer|min:1',
            'max_users' => 'integer|min:1',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'grace_period_days' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $subscriptionPlan->update($data);
        $this->syncPermissionsAndLimits($subscriptionPlan, $request);

        return response()->json($subscriptionPlan->load(['modules', 'planPermissions', 'limits']));
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $plan = SubscriptionPlan::onlyTrashed()->findOrFail($id);
        $plan->restore();
        return response()->json($plan);
    }

    public function forceDelete(int $id)
    {
        $plan = SubscriptionPlan::onlyTrashed()->findOrFail($id);
        $plan->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * GET /api/subscription-plans/{id}/matrix
     * Full plan detail: modules + permissions + limits
     */
    public function matrix(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load(['modules', 'planPermissions', 'limits']);

        return response()->json([
            'plan' => [
                'id' => $subscriptionPlan->id,
                'code' => $subscriptionPlan->code,
                'name' => $subscriptionPlan->name,
                'tier' => $subscriptionPlan->tier,
                'price' => $subscriptionPlan->price,
                'monthly_price' => $subscriptionPlan->monthly_price,
                'max_users' => $subscriptionPlan->max_users,
                'is_active' => $subscriptionPlan->is_active,
            ],
            'modules' => $subscriptionPlan->modules->map(function ($m) {
                return [
                    'key' => $m->key,
                    'name' => $m->name,
                    'can_view' => $m->pivot->can_view,
                    'can_create' => $m->pivot->can_create,
                    'can_edit' => $m->pivot->can_edit,
                    'can_delete' => $m->pivot->can_delete,
                ];
            }),
            'permissions' => $subscriptionPlan->planPermissions->pluck('permission_code')->toArray(),
            'limits' => $subscriptionPlan->limits->mapWithKeys(fn($l) => [$l->key => (int) $l->value]),
        ]);
    }

    private function syncPermissionsAndLimits(SubscriptionPlan $plan, Request $request): void
    {
        if ($request->has('permissions')) {
            $plan->planPermissions()->delete();
            foreach ($request->permissions as $permCode) {
                $plan->planPermissions()->create(['permission_code' => $permCode]);
            }
        }

        if ($request->has('limits')) {
            $plan->limits()->delete();
            foreach ($request->limits as $key => $value) {
                $plan->limits()->create(['key' => $key, 'value' => (string) $value]);
            }
        }

        if ($request->has('module_permissions')) {
            $syncData = [];
            foreach ($request->module_permissions as $moduleKey => $perms) {
                $module = \App\Models\Settings\AdminModule::where('key', $moduleKey)->first();
                if ($module) {
                    $syncData[$module->id] = array_merge($perms, [
                        'sort_order' => $module->sort_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $plan->modules()->sync($syncData);
        }
    }
}
