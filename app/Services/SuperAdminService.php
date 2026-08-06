<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Module;
use App\Models\Feature;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class SuperAdminService
{
    /**
     * Get platform-wide overview stats.
     */
    public function getPlatformStats(): array
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();

        $totalModules = Module::count();
        $enabledModules = Module::where('is_enabled', true)->count();

        $totalFeatures = Feature::count();
        $totalPermissions = Permission::count();

        // Subscription stats
        $activeSubscriptions = CompanySubscription::where('status', 'active')->count();
        $expiredSubscriptions = CompanySubscription::where('status', 'expired')
            ->orWhere('end_date', '<', now())->count();

        // Revenue (if subscriptions have amounts)
        $monthlyRevenue = CompanySubscription::where('status', 'active')
            ->sum('amount');

        // Plan distribution
        $planDistribution = CompanySubscription::select('subscription_plan_id', DB::raw('count(*) as total'))
            ->where('status', 'active')
            ->groupBy('subscription_plan_id')
            ->with('plan')
            ->get()
            ->map(fn($s) => [
                'plan_id' => $s->subscription_plan_id,
                'plan_name' => $s->plan?->name ?? 'Unknown',
                'plan_code' => $s->plan?->code ?? '?',
                'count' => $s->total,
            ])
            ->toArray();

        return [
            'companies' => [
                'total' => $totalCompanies,
                'active' => $activeCompanies,
                'inactive' => $totalCompanies - $activeCompanies,
            ],
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
            ],
            'modules' => [
                'total' => $totalModules,
                'enabled' => $enabledModules,
            ],
            'features' => ['total' => $totalFeatures],
            'permissions' => ['total' => $totalPermissions],
            'subscriptions' => [
                'active' => $activeSubscriptions,
                'expired' => $expiredSubscriptions,
                'monthly_revenue' => (float) $monthlyRevenue,
            ],
            'plan_distribution' => $planDistribution,
        ];
    }

    /**
     * Get all companies with their subscription details.
     */
    public function listCompanies(array $filters = []): array
    {
        $query = Company::query()
            ->with('subscription.plan')
            ->withCount(['users' => fn($q) => $q->where('is_active', true)]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%")
                  ->orWhere('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['plan_id'])) {
            $query->whereHas('subscription', fn($q) => $q->where('subscription_plan_id', $filters['plan_id']));
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $perPage = $filters['per_page'] ?? 25;
        $companies = $query->orderBy('id')->paginate($perPage);

        return [
            'data' => $companies->items(),
            'total' => $companies->total(),
            'per_page' => $perPage,
            'current_page' => $companies->currentPage(),
            'last_page' => $companies->lastPage(),
        ];
    }

    /**
     * Get company details.
     */
    public function getCompany(int $companyId): ?array
    {
        $company = Company::with('subscription.plan')
            ->withCount(['users' => fn($q) => $q->where('is_active', true)])
            ->find($companyId);

        if (!$company) return null;

        $company->load(['users' => fn($q) => $q->select('id', 'usercode', 'name', 'email', 'is_active', 'company_id')
            ->with('roles:id,name,code')
        ]);

        $company->load('subscription.plan.features');

        return [
            'company' => $company,
            'subscription' => $company->subscription,
            'features' => $company->subscription?->plan?->features?->pluck('code') ?? [],
            'modules' => \App\Services\ModuleRegistry::enabled(),
        ];
    }

    /**
     * Update company subscription.
     */
    public function updateCompanySubscription(int $companyId, array $data): ?CompanySubscription
    {
        $subscription = CompanySubscription::where('company_id', $companyId)->first();

        if (!$subscription) {
            $subscription = new CompanySubscription();
            $subscription->company_id = $companyId;
        }

        if (isset($data['subscription_plan_id'])) {
            $subscription->subscription_plan_id = $data['subscription_plan_id'];
        }
        if (isset($data['status'])) {
            $subscription->status = $data['status'];
        }
        if (isset($data['start_date'])) {
            $subscription->start_date = $data['start_date'];
        }
        if (isset($data['end_date'])) {
            $subscription->end_date = $data['end_date'];
        }
        if (isset($data['amount'])) {
            $subscription->amount = $data['amount'];
        }

        $subscription->save();
        return $subscription->fresh('plan');
    }

    /**
     * Get subscription plans with feature counts.
     */
    public function getPlans(): array
    {
        return SubscriptionPlan::withCount('features')
            ->with(['features' => fn($q) => $q->select('id', 'code', 'name', 'category')])
            ->get()
            ->toArray();
    }

    /**
     * Get platform health overview.
     */
    public function getHealth(): array
    {
        $dbSize = DB::select("SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size()");
        $dbSizeMB = round(($dbSize[0]->size ?? 0) / 1024 / 1024, 2);

        return [
            'database' => [
                'size_mb' => $dbSizeMB,
                'tables' => count(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")),
            ],
            'queue' => app(MonitoringService::class)->getQueueStats(),
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server' => php_uname('s') . ' ' . php_uname('r'),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ],
            'modules' => [
                'installed' => Module::where('status', 'installed')->count(),
                'enabled' => Module::where('is_enabled', true)->count(),
                'disabled' => Module::where('is_enabled', false)->count(),
            ],
        ];
    }
}
