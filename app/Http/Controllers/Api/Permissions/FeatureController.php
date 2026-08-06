<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\Settings\Feature;
use App\Services\FeatureService;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * GET /api/features
     * List all features with plan assignment status.
     */
    public function index(Request $request)
    {
        $query = Feature::orderBy('category')->orderBy('sort_order');
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * GET /api/features/check/{code}
     * Check if current company has a feature enabled.
     */
    public function check(Request $request, string $code, FeatureService $featureService)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['enabled' => false], 403);
        }

        $company = $user->company;
        $enabled = $featureService->isEnabled($company, $code);

        return response()->json([
            'feature' => $code,
            'enabled' => $enabled,
        ]);
    }

    /**
     * POST /api/features/check-batch
     * Check multiple features at once.
     */
    public function checkBatch(Request $request, FeatureService $featureService)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['features' => []], 403);
        }

        $request->validate([
            'features' => 'required|array',
        ]);

        $company = $user->company;
        $result = $featureService->checkBatch($company, $request->features);

        return response()->json(['features' => $result]);
    }

    /**
     * GET /api/features/enabled
     * Get all enabled features for current company.
     */
    public function enabled(Request $request, FeatureService $featureService)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['features' => []], 403);
        }

        $company = $user->company;
        $features = $featureService->getEnabledFeatures($company);

        return response()->json(['features' => $features]);
    }
}
