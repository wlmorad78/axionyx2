<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\ApiAuditLog;
use App\Models\ApiClient;
use App\Models\ApiPermission;
use App\Models\ApiRateLimit;
use App\Models\ApiRequestLog;
use App\Models\ApiToken;
use App\Models\EInvoiceProvider;
use App\Models\EInvoiceTransaction;
use App\Models\ExternalDocument;
use App\Models\ExternalDocumentLog;
use App\Models\IntegrationAccount;
use App\Models\IntegrationEndpoint;
use App\Models\IntegrationErrorLog;
use App\Models\IntegrationEvent;
use App\Models\IntegrationEventSubscription;
use App\Models\IntegrationJob;
use App\Models\IntegrationJobRun;
use App\Models\IntegrationProvider;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\SyncBatch;
use App\Models\SyncLog;
use App\Models\MobileDevice;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use App\Models\WebhookSubscription;
use Illuminate\Database\Seeder;

class IntegrationFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        // E-Invoice Providers (global)
        EInvoiceProvider::updateOrCreate(['provider_name' => 'الهيئة المصرية للمالية العامة - Egyptian Tax Authority'], ['provider_type' => 'government']);
        EInvoiceProvider::updateOrCreate(['provider_name' => 'هيئة الزكاة والضريبة - ZATCA'], ['provider_type' => 'government']);

        // Message Templates (global)
        MessageTemplate::updateOrCreate(['template_code' => 'MSG-SMS'], ['template_name' => 'قالب رسالة SMS', 'message_body' => 'مرحباً {{name}}، رصيدك الحالي هو {{balance}} ج.م', 'channel' => 'sms']);
        MessageTemplate::updateOrCreate(['template_code' => 'MSG-EMAIL'], ['template_name' => 'قالب بريد إلكتروني', 'message_body' => 'مرحباً {{name}}، فاتورتك رقم {{invoice_number}} جاهزة للدفع', 'channel' => 'sms']);

        foreach ($companies as $company) {
            $adminUser = User::where('company_id', $company->id)->first();
            $employee = \App\Models\Employee::where('company_id', $company->id)->first();

            // Integration Providers
            $providers = [
                ['provider_code' => 'INT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-SAP', 'provider_name' => 'نظام SAP - SAP System', 'provider_type' => 'erp'],
                ['provider_code' => 'INT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-CRM', 'provider_name' => 'نظام CRM - CRM System', 'provider_type' => 'crm'],
            ];

            foreach ($providers as $p) {
                $provider = IntegrationProvider::updateOrCreate(
                    ['provider_code' => $p['provider_code']],
                    ['company_id' => $company->id, 'provider_name' => $p['provider_name'], 'provider_type' => $p['provider_type'], 'is_active' => true]
                );

                // Integration Accounts
                $account = IntegrationAccount::updateOrCreate(
                    ['integration_provider_id' => $provider->id, 'account_name' => 'حساب الاتصال'],
                    ['api_key' => 'test_key_' . $company->id, 'is_active' => true]
                );

                // Integration Endpoints
                IntegrationEndpoint::updateOrCreate(
                    ['integration_provider_id' => $provider->id, 'endpoint_name' => 'sync_customers'],
                    ['endpoint_url' => '/api/customers', 'http_method' => 'POST']
                );

                // Integration Events
                IntegrationEvent::updateOrCreate(
                    ['event_code' => 'IE-' . $p['provider_code'] . '-CUSTOMER_CREATED'],
                    ['company_id' => $company->id, 'event_name' => 'customer_created', 'entity_type' => 'Customer', 'is_active' => true]
                );
            }

            // Integration Event Subscriptions
            $event = IntegrationEvent::first();
            $account = IntegrationAccount::where('integration_provider_id', IntegrationProvider::where('company_id', $company->id)->first()?->id)->first();
            if ($event && $account) {
                IntegrationEventSubscription::updateOrCreate(
                    ['integration_account_id' => $account->id, 'integration_event_id' => $event->id],
                    ['is_enabled' => true]
                );
            }

            // API Clients
            $apiClient = ApiClient::updateOrCreate(
                ['client_id' => 'client_' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'client_name' => 'تطبيق الموبايل - Mobile App',
                    'client_secret' => 'secret_' . bin2hex(random_bytes(16)),
                    'is_active' => true,
                ]
            );

            // API Tokens
            ApiToken::updateOrCreate(
                ['access_token' => 'token_' . bin2hex(random_bytes(32))],
                ['api_client_id' => $apiClient->id, 'expires_at' => now()->addYear()]
            );

            // API Permissions
            ApiPermission::updateOrCreate(
                ['api_client_id' => $apiClient->id, 'resource_name' => 'customers'],
                ['can_create' => true, 'can_update' => true, 'can_delete' => false, 'can_view' => true]
            );

            // API Rate Limits
            ApiRateLimit::updateOrCreate(
                ['api_client_id' => $apiClient->id],
                ['requests_per_minute' => 60, 'requests_per_hour' => 1000, 'requests_per_day' => 10000]
            );

            // API Request Logs
            ApiRequestLog::create([
                'api_client_id' => $apiClient->id,
                'request_method' => 'GET',
                'request_url' => '/api/customers',
                'response_code' => 200,
                'ip_address' => '127.0.0.1',
            ]);

            // Webhook Endpoints
            $webhook = WebhookEndpoint::updateOrCreate(
                ['company_id' => $company->id, 'webhook_name' => 'Invoice Webhook', 'target_url' => 'https://webhook.example.com/events'],
                ['secret_key' => 'whsec_' . bin2hex(random_bytes(16)), 'is_active' => true]
            );

            // Webhook Subscriptions
            if ($event) {
                WebhookSubscription::updateOrCreate(
                    ['webhook_endpoint_id' => $webhook->id, 'integration_event_id' => $event->id],
                    []
                );
            }

            // Webhook Logs
            WebhookLog::create([
                'webhook_endpoint_id' => $webhook->id,
                'payload' => ['invoice_id' => 1, 'amount' => 1000],
                'response_code' => 200,
                'status' => 'SUCCESS',
                'sent_at' => now(),
            ]);

            // E-Invoice Transactions
            $salesInvoice = \App\Models\SalesInvoice::where('company_id', $company->id)->first();
            $eip = EInvoiceProvider::first();
            if ($salesInvoice && $eip) {
                EInvoiceTransaction::updateOrCreate(
                    ['sales_invoice_id' => $salesInvoice->id, 'provider_id' => $eip->id],
                    [
                        'external_reference' => 'EI-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001',
                        'status' => 'sent',
                        'submitted_at' => now(),
                    ]
                );
            }

            // Sync Batches
            $syncBatch = SyncBatch::updateOrCreate(
                ['device_id' => 'device_' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
                [
                    'sales_rep_id' => $employee?->id,
                    'sync_start' => now()->subHour(),
                    'sync_end' => now(),
                    'status' => 'completed',
                ]
            );

            // Sync Logs
            SyncLog::create([
                'sync_batch_id' => $syncBatch->id,
                'table_name' => 'customers',
                'record_id' => 1,
                'operation' => 'create',
                'status' => 'success',
            ]);

            // Mobile Devices
            MobileDevice::updateOrCreate(
                ['device_uuid' => 'device_' . bin2hex(random_bytes(16))],
                [
                    'device_name' => 'Samsung Galaxy',
                    'sales_rep_id' => $employee?->id,
                    'last_sync_at' => now(),
                    'status' => 'active',
                ]
            );

            // Integration Jobs
            if ($account) {
                $job = IntegrationJob::updateOrCreate(
                    ['integration_account_id' => $account->id, 'job_name' => 'sync_customers'],
                    ['schedule_type' => 'REALTIME', 'is_active' => true]
                );

                IntegrationJobRun::create([
                    'integration_job_id' => $job->id,
                    'started_at' => now()->subHour(),
                    'ended_at' => now(),
                    'status' => 'SUCCESS',
                    'records_processed' => 50,
                ]);
            }

            // External Documents
            $extDoc = ExternalDocument::updateOrCreate(
                ['company_id' => $company->id, 'external_document_no' => 'ED-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'provider_id' => IntegrationProvider::where('company_id', $company->id)->first()?->id,
                    'entity_type' => 'SalesInvoice',
                    'entity_id' => $salesInvoice?->id,
                    'status' => 'PENDING',
                ]
            );

            ExternalDocumentLog::create([
                'external_document_id' => $extDoc->id,
                'request_payload' => ['document_no' => 'ED-001'],
                'response_payload' => ['status' => 'success'],
                'status' => 'PENDING',
            ]);

            // Integration Error Logs
            if ($account) {
                IntegrationErrorLog::create([
                    'integration_account_id' => $account->id,
                    'error_code' => 'CONN_TIMEOUT',
                    'error_message' => 'Connection timeout after 30 seconds',
                ]);
            }

            // API Audit Logs
            ApiAuditLog::create([
                'api_client_id' => $apiClient->id,
                'action_type' => 'token_created',
                'resource_name' => 'api_tokens',
                'resource_id' => ApiToken::where('api_client_id', $apiClient->id)->first()?->id,
                'ip_address' => '127.0.0.1',
            ]);
        }
    }
}
