<?php

namespace App\Http\Controllers\Api\AppUpdate;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppVersionResource;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function __construct(
        private readonly \App\Services\AppVersionService $versionService
    ) {}

    public function latest(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'nullable|string|in:android,ios,windows',
        ]);

        $platform = $request->input('platform', 'android');

        $version = $this->versionService->getLatestVersion($platform);

        if (!$version) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No version available for this platform',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new AppVersionResource($version),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => 'required|string',
            'build' => 'required|integer|min:1',
            'platform' => 'required|string|in:android,ios,windows',
            'download_url' => 'nullable|string',
            'force_update' => 'nullable|boolean',
            'release_notes' => 'nullable|array',
            'release_notes.*' => 'string',
            'release_date' => 'nullable|date',
            'minimum_supported_version' => 'nullable|string',
            'minimum_supported_build' => 'nullable|integer|min:0',
            'file_size' => 'nullable|integer|min:0',
            'checksum' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $version = $this->versionService->createVersion($validated);

        return response()->json([
            'success' => true,
            'data' => new AppVersionResource($version),
            'message' => 'Version created successfully',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'nullable|string|in:android,ios,windows',
        ]);

        $query = AppVersion::query()->orderByDesc('build');

        if ($request->has('platform')) {
            $query->forPlatform($request->input('platform'));
        }

        $versions = $query->get();

        return response()->json([
            'success' => true,
            'data' => AppVersionResource::collection($versions),
        ]);
    }

    public function show(AppVersion $appVersion): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new AppVersionResource($appVersion),
        ]);
    }

    public function update(Request $request, AppVersion $appVersion): JsonResponse
    {
        $validated = $request->validate([
            'version' => 'sometimes|string',
            'build' => 'sometimes|integer|min:1',
            'platform' => 'sometimes|string|in:android,ios,windows',
            'download_url' => 'nullable|string',
            'force_update' => 'nullable|boolean',
            'release_notes' => 'nullable|array',
            'release_notes.*' => 'string',
            'release_date' => 'nullable|date',
            'minimum_supported_version' => 'nullable|string',
            'minimum_supported_build' => 'nullable|integer|min:0',
            'file_size' => 'nullable|integer|min:0',
            'checksum' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $appVersion->update($validated);

        return response()->json([
            'success' => true,
            'data' => new AppVersionResource($appVersion),
            'message' => 'Version updated successfully',
        ]);
    }

    public function destroy(AppVersion $appVersion): JsonResponse
    {
        $appVersion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Version deleted successfully',
        ]);
    }
}
