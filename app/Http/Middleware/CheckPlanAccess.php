<?php

namespace App\Http\Middleware;

use App\Models\AdminScreen;
use App\Models\CompanySubscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $companyId = session('company_id') ?? $user->company_id;

        if (!$companyId) {
            return $next($request);
        }

        $subscription = CompanySubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return $next($request);
        }

        $plan = $subscription->plan;

        $route = $request->route();
        $screenKey = $route->parameter('key') ?? $this->resolveScreenKeyFromPath($request->path());

        if (!$screenKey) {
            return $next($request);
        }

        $screen = AdminScreen::where('key', $screenKey)->first();

        if (!$screen) {
            return $next($request);
        }

        $moduleKey = $screen->module->key ?? null;

        if ($moduleKey && !$plan->hasModule($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'هذه الميزة غير متاحة في باقتك الحالية',
                    'upgrade_url' => route('subscription-plans.index'),
                    'current_plan' => $plan->name,
                ], 403);
            }

            return redirect()->route('subscription-plans.index')
                ->with('error', "الباقة الحالية ({$plan->name}) لا تدعم هذه الميزة. يرجى ترقية الباقة.");
        }

        return $next($request);
    }

    private function resolveScreenKeyFromPath(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));

        if (count($segments) >= 2 && $segments[0] === 'admin' && $segments[1] === 'screens') {
            return $segments[2] ?? null;
        }

        if (count($segments) >= 2 && $segments[0] === 'admin') {
            return $segments[1];
        }

        return null;
    }
}
