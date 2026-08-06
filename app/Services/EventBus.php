<?php

namespace App\Services;

use App\Models\EventDefinition;
use App\Models\EventLog;
use App\Models\EventSubscription;
use Illuminate\Support\Facades\Log;

class EventBus
{
    /**
     * Fire an event.
     */
    public static function fire(string $eventCode, array $payload = [], ?int $companyId = null, ?int $userId = null): EventLog
    {
        $definition = EventDefinition::where('code', $eventCode)->where('is_enabled', true)->first();

        if (!$definition) {
            Log::warning("EventBus: Event '{$eventCode}' not found or disabled");
            return new EventLog();
        }

        $log = EventLog::create([
            'event_definition_id' => $definition->id,
            'company_id' => $companyId,
            'user_id' => $userId,
            'payload' => $payload,
            'status' => 'fired',
            'fired_at' => now(),
        ]);

        // Process subscriptions
        $subscriptions = EventSubscription::where('event_definition_id', $definition->id)
            ->where('is_enabled', true)
            ->orderBy('priority', 'desc')
            ->get();

        $processedBy = [];

        foreach ($subscriptions as $sub) {
            try {
                $handler = new $sub->handler_class;
                $handler->handle($payload, $companyId, $userId);
                $processedBy[] = [
                    'module' => $sub->module_code,
                    'handler' => $sub->handler_class,
                    'status' => 'success',
                ];
            } catch (\Exception $e) {
                Log::error("EventBus: Handler {$sub->handler_class} failed", [
                    'event' => $eventCode,
                    'error' => $e->getMessage(),
                ]);
                $processedBy[] = [
                    'module' => $sub->module_code,
                    'handler' => $sub->handler_class,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $log->update([
            'status' => 'processed',
            'processed_by' => $processedBy,
        ]);

        // Dispatch webhooks
        try {
            WebhookService::dispatch($eventCode, $payload, $companyId);
        } catch (\Exception $e) {
            Log::warning("EventBus: Webhook dispatch failed for {$eventCode}", [
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Subscribe a module to an event.
     */
    public static function subscribe(string $eventCode, string $moduleCode, string $handlerClass, int $priority = 0, array $config = []): EventSubscription
    {
        $definition = EventDefinition::where('code', $eventCode)->first();
        if (!$definition) {
            throw new \RuntimeException("Event '{$eventCode}' not found");
        }

        return EventSubscription::updateOrCreate(
            [
                'event_definition_id' => $definition->id,
                'module_code' => $moduleCode,
                'handler_class' => $handlerClass,
            ],
            [
                'priority' => $priority,
                'config' => $config,
                'is_enabled' => true,
            ]
        );
    }

    /**
     * Unsubscribe a module from an event.
     */
    public static function unsubscribe(string $eventCode, string $moduleCode, string $handlerClass): bool
    {
        $definition = EventDefinition::where('code', $eventCode)->first();
        if (!$definition) return false;

        return EventSubscription::where('event_definition_id', $definition->id)
            ->where('module_code', $moduleCode)
            ->where('handler_class', $handlerClass)
            ->delete() > 0;
    }

    /**
     * Get all subscriptions for an event.
     */
    public static function getSubscriptions(string $eventCode): array
    {
        $definition = EventDefinition::where('code', $eventCode)->first();
        if (!$definition) return [];

        return EventSubscription::where('event_definition_id', $definition->id)
            ->where('is_enabled', true)
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get event history for a company.
     */
    public static function getHistory(int $companyId, int $limit = 50): array
    {
        return EventLog::where('company_id', $companyId)
            ->with('eventDefinition:id,code,name,name_ar')
            ->latest('fired_at')
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * Register default events.
     */
    public static function registerDefaults(): void
    {
        $events = [
            ['code' => 'invoice.posted',       'name' => 'Invoice Posted',         'name_ar' => 'تم ترحيل الفاتورة',     'category' => 'sales',     'source_module' => 'sales'],
            ['code' => 'invoice.cancelled',    'name' => 'Invoice Cancelled',      'name_ar' => 'تم إلغاء الفاتورة',     'category' => 'sales',     'source_module' => 'sales'],
            ['code' => 'invoice.created',      'name' => 'Invoice Created',        'name_ar' => 'تم إنشاء الفاتورة',     'category' => 'sales',     'source_module' => 'sales'],
            ['code' => 'collection.created',   'name' => 'Collection Created',     'name_ar' => 'تم إنشاء تحصيل',        'category' => 'sales',     'source_module' => 'sales'],
            ['code' => 'return.posted',        'name' => 'Return Posted',          'name_ar' => 'تم ترحيل المرتجع',      'category' => 'sales',     'source_module' => 'sales'],
            ['code' => 'stock.low',            'name' => 'Low Stock Alert',        'name_ar' => 'تنبيه نقص المخزون',     'category' => 'inventory', 'source_module' => 'inventory'],
            ['code' => 'stock.transfer',       'name' => 'Stock Transfer',         'name_ar' => 'تحويل مخزون',           'category' => 'inventory', 'source_module' => 'inventory'],
            ['code' => 'purchase.posted',      'name' => 'Purchase Posted',        'name_ar' => 'تم ترحيل المشتريات',    'category' => 'purchases', 'source_module' => 'purchases'],
            ['code' => 'payment.made',         'name' => 'Payment Made',           'name_ar' => 'تم الدفع',              'category' => 'treasury',  'source_module' => 'treasury'],
            ['code' => 'employee.created',     'name' => 'Employee Created',       'name_ar' => 'تم إضافة موظف',         'category' => 'hr',        'source_module' => 'hr'],
            ['code' => 'attendance.marked',    'name' => 'Attendance Marked',      'name_ar' => 'تم تسجيل الحضور',       'category' => 'hr',        'source_module' => 'hr'],
            ['code' => 'approval.requested',   'name' => 'Approval Requested',     'name_ar' => 'تم طلب موافقة',         'category' => 'workflow',  'source_module' => 'workflow'],
            ['code' => 'approval.approved',    'name' => 'Approval Approved',      'name_ar' => 'تمت الموافقة',          'category' => 'workflow',  'source_module' => 'workflow'],
            ['code' => 'approval.rejected',    'name' => 'Approval Rejected',      'name_ar' => 'تم الرفض',              'category' => 'workflow',  'source_module' => 'workflow'],
            ['code' => 'user.login',           'name' => 'User Login',             'name_ar' => 'تسجيل دخول',            'category' => 'system',    'source_module' => 'settings'],
            ['code' => 'user.logout',          'name' => 'User Logout',            'name_ar' => 'تسجيل خروج',            'category' => 'system',    'source_module' => 'settings'],
            ['code' => 'setting.changed',      'name' => 'Setting Changed',        'name_ar' => 'تم تغيير الإعداد',       'category' => 'system',    'source_module' => 'settings'],
            ['code' => 'module.installed',     'name' => 'Module Installed',       'name_ar' => 'تم تثبيت موديول',        'category' => 'system',    'source_module' => null],
            ['code' => 'module.uninstalled',   'name' => 'Module Uninstalled',     'name_ar' => 'تم إزالة موديول',        'category' => 'system',    'source_module' => null],
        ];

        foreach ($events as $event) {
            EventDefinition::updateOrCreate(
                ['code' => $event['code']],
                $event
            );
        }
    }
}
