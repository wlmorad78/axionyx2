<?php

namespace Database\Seeders;

use App\Services\EventBus;
use App\Models\EventSubscription;
use Illuminate\Database\Seeder;

class EventBusSeeder extends Seeder
{
    public function run(): void
    {
        // Register all default events
        EventBus::registerDefaults();

        // Wire up cross-module subscriptions
        $subscriptions = [
            // When invoice is posted → update stock
            ['invoice.posted', 'inventory', \App\Services\Handlers\UpdateStockOnInvoice::class, 10],
            // When invoice is posted → create accounting entry
            ['invoice.posted', 'accounting', \App\Services\Handlers\CreateJournalOnInvoice::class, 20],
            // When invoice is posted → send notification
            ['invoice.posted', 'notifications', \App\Services\Handlers\NotifyOnInvoice::class, 30],
            // When invoice is posted → log audit
            ['invoice.posted', 'audit', \App\Services\Handlers\AuditOnInvoice::class, 40],
            // When stock is low → send alert
            ['stock.low', 'notifications', \App\Services\Handlers\NotifyOnLowStock::class, 10],
            // When payment is made → update invoice
            ['payment.made', 'sales', \App\Services\Handlers\UpdateInvoiceOnPayment::class, 10],
            // When approval requested → send notification
            ['approval.requested', 'notifications', \App\Services\Handlers\NotifyOnApproval::class, 10],
            // When employee created → send welcome
            ['employee.created', 'notifications', \App\Services\Handlers\NotifyOnEmployee::class, 10],
        ];

        foreach ($subscriptions as [$eventCode, $module, $handler, $priority]) {
            try {
                EventBus::subscribe($eventCode, $module, $handler, $priority);
            } catch (\Exception $e) {
                // Skip if event or handler doesn't exist yet
            }
        }
    }
}
