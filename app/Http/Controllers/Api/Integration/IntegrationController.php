<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\ApiKey;
use App\Models\ApiKeyLog;
use App\Services\WebhookService;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    // ============================================================
    // WEBHOOKS
    // ============================================================

    /**
     * GET /api/webhooks
     */
    public function webhookIndex(Request $request)
    {
        $webhooks = Webhook::where('company_id', $request->user()->company_id)
            ->withCount('deliveries')
            ->latest()
            ->paginate(20);

        return response()->json($webhooks);
    }

    /**
     * POST /api/webhooks
     */
    public function webhookStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'url' => 'required|url',
            'method' => 'nullable|string|in:POST,PUT,PATCH',
            'events' => 'required|array',
            'headers' => 'nullable|array',
            'retry_count' => 'nullable|integer|min:0|max:5',
            'timeout_seconds' => 'nullable|integer|min:1|max:120',
        ]);

        $validated['company_id'] = $request->user()->company_id;
        $validated['created_by'] = $request->user()->id;

        $webhook = Webhook::create($validated);

        return response()->json([
            'data' => $webhook,
            'message' => 'Webhook created. Secret: ' . $webhook->secret,
        ], 201);
    }

    /**
     * GET /api/webhooks/{id}
     */
    public function webhookShow(Webhook $webhook)
    {
        $webhook->loadCount('deliveries');
        return response()->json(['data' => $webhook]);
    }

    /**
     * PUT /api/webhooks/{id}
     */
    public function webhookUpdate(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'url' => 'sometimes|url',
            'method' => 'nullable|string',
            'events' => 'sometimes|array',
            'headers' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'retry_count' => 'nullable|integer',
            'timeout_seconds' => 'nullable|integer',
        ]);

        $webhook->update($validated);
        return response()->json(['data' => $webhook]);
    }

    /**
     * DELETE /api/webhooks/{id}
     */
    public function webhookDestroy(Webhook $webhook)
    {
        $webhook->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * POST /api/webhooks/{id}/test
     * Send a test payload.
     */
    public function webhookTest(Webhook $webhook)
    {
        WebhookService::deliver($webhook, 'test.ping', [
            'message' => 'Test webhook delivery',
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json(['message' => 'Test payload dispatched']);
    }

    /**
     * GET /api/webhooks/{id}/deliveries
     */
    public function webhookDeliveries(Webhook $webhook)
    {
        $deliveries = $webhook->deliveries()
            ->latest()
            ->paginate(20);

        return response()->json($deliveries);
    }

    // ============================================================
    // API KEYS
    // ============================================================

    /**
     * GET /api/api-keys
     */
    public function apiKeyIndex(Request $request)
    {
        $keys = ApiKey::where('company_id', $request->user()->company_id)
            ->select('id', 'code', 'name', 'key_prefix', 'scopes', 'is_active', 'usage_count', 'last_used_at', 'expires_at', 'created_at')
            ->latest()
            ->get();

        return response()->json(['data' => $keys]);
    }

    /**
     * POST /api/api-keys
     * Returns the plain key ONCE.
     */
    public function apiKeyStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'scopes' => 'nullable|array',
            'rate_limit' => 'nullable|array',
            'ip_whitelist' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $validated['company_id'] = $request->user()->company_id;
        $validated['user_id'] = $request->user()->id;

        $result = ApiKeyService::create($validated);

        return response()->json([
            'data' => $result['key']->only('id', 'code', 'name', 'key_prefix'),
            'plain_key' => $result['plain_key'],
            'message' => 'Save this key now. It will not be shown again.',
        ], 201);
    }

    /**
     * DELETE /api/api-keys/{id}
     */
    public function apiKeyDestroy(ApiKey $apiKey)
    {
        $apiKey->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * PATCH /api/api-keys/{id}/toggle
     */
    public function apiKeyToggle(ApiKey $apiKey)
    {
        $apiKey->update(['is_active' => !$apiKey->is_active]);
        return response()->json(['data' => $apiKey->only('id', 'is_active')]);
    }

    /**
     * GET /api/api-keys/{id}/logs
     */
    public function apiKeyLogs(ApiKey $apiKey)
    {
        $logs = $apiKey->logs()
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    /**
     * GET /api/events/available
     * List all available event codes for webhook registration.
     */
    public function availableEvents()
    {
        $events = \App\Models\EventDefinition::pluck('code', 'name');
        return response()->json(['data' => $events]);
    }
}
