<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch all active webhooks for a given event.
     */
    public static function dispatch(string $eventCode, array $payload, ?int $companyId = null): void
    {
        $query = Webhook::where('is_active', true);

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        $webhooks = $query->get()->filter->matchesEvent($eventCode);

        foreach ($webhooks as $webhook) {
            dispatch(fn() => self::deliver($webhook, $eventCode, $payload))
                ->afterCommit();
        }
    }

    /**
     * Deliver a single webhook payload.
     */
    public static function deliver(Webhook $webhook, string $eventCode, array $payload): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_code' => $eventCode,
            'payload' => $eventCode . '.' . json_encode($payload),
            'status' => 'pending',
        ]);

        try {
            $start = microtime(true);

            $headers = array_merge([
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $eventCode,
                'X-Webhook-Delivery' => $delivery->id,
                'X-Webhook-Signature' => self::sign($webhook->secret, $delivery->payload),
            ], $webhook->headers ?? []);

            $response = Http::withHeaders($headers)
                ->timeout($webhook->timeout_seconds)
                ->retry($webhook->retry_count, 500)
                ->send($webhook->method, $webhook->url, [
                    'body' => json_encode([
                        'event' => $eventCode,
                        'timestamp' => now()->toIso8601String(),
                        'delivery_id' => $delivery->id,
                        'data' => $payload,
                    ]),
                ]);

            $duration = (int) ((microtime(true) - $start) * 1000);

            $delivery->update([
                'status_code' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body(),
                'duration_ms' => $duration,
                'status' => $response->successful() ? 'success' : 'failed',
                'delivered_at' => now(),
            ]);

            $webhook->update([
                'last_triggered_at' => now(),
                'success_count' => $webhook->success_count + ($response->successful() ? 1 : 0),
                'failure_count' => $webhook->failure_count + ($response->failed() ? 1 : 0),
            ]);

        } catch (\Exception $e) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'delivered_at' => now(),
            ]);

            $webhook->update([
                'last_triggered_at' => now(),
                'failure_count' => $webhook->failure_count + 1,
            ]);

            Log::warning("Webhook delivery failed: {$webhook->code}", [
                'event' => $eventCode,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate HMAC signature for payload verification.
     */
    public static function sign(string $secret, string $payload): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify a webhook signature.
     */
    public static function verify(string $secret, string $payload, string $signature): bool
    {
        $expected = self::sign($secret, $payload);
        return hash_equals($expected, $signature);
    }
}
