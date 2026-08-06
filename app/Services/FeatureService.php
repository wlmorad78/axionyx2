<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Feature;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    /**
     * Check if a feature is enabled for a company.
     * Resolves: company → subscription → plan → feature
     */
    public function isEnabled(Company $company, string $featureCode): bool
    {
        $cacheKey = "feature:{$company->id}:{$featureCode}";

        return Cache::remember($cacheKey, 3600, function () use ($company, $featureCode) {
            $subscription = $company->subscription;
            if (!$subscription) {
                return false;
            }

            $plan = $subscription->plan;
            if (!$plan || !$plan->is_active) {
                return false;
            }

            // Check if subscription is expired
            if ($subscription->end_date && $subscription->end_date->isPast()) {
                return false;
            }

            return $plan->features()
                ->where('features.code', $featureCode)
                ->where('plan_features.is_enabled', true)
                ->exists();
        });
    }

    /**
     * Get all enabled feature codes for a company.
     */
    public function getEnabledFeatures(Company $company): array
    {
        $subscription = $company->subscription;
        if (!$subscription) {
            return [];
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return [];
        }

        return $plan->features()
            ->where('plan_features.is_enabled', true)
            ->pluck('features.code')
            ->toArray();
    }

    /**
     * Check multiple features at once.
     */
    public function checkBatch(Company $company, array $featureCodes): array
    {
        $enabled = $this->getEnabledFeatures($company);
        $result = [];
        foreach ($featureCodes as $code) {
            $result[$code] = in_array($code, $enabled);
        }
        return $result;
    }

    /**
     * Clear feature cache for a company.
     */
    public function clearCache(int $companyId): void
    {
        $features = Feature::pluck('code')->toArray();
        foreach ($features as $code) {
            Cache::forget("feature:{$companyId}:{$code}");
        }
    }
}
