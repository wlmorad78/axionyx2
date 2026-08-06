<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Integration\ApiClient;
use App\Models\Integration\ApiKey;
use App\Models\Integration\IntegrationEvent;
use App\Models\Integration\IntegrationProvider;
use App\Models\Integration\Webhook;
use App\Models\Integration\WebhookEndpoint;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'INT-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'INT-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── ApiClient ──

    public function test_api_client_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = ApiClient::create([
            'client_name' => 'Test Client', 'client_id' => 'cli_a1b2c3', 'client_secret' => 'secret123',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_api_client_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        ApiClient::create(['client_name' => 'A', 'client_id' => 'cli_a1', 'client_secret' => 's1']);

        CompanyContext::override($this->companyB->id);
        ApiClient::create(['client_name' => 'B', 'client_id' => 'cli_b1', 'client_secret' => 's2']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, ApiClient::forCompany()->get());
    }

    // ── ApiKey ──

    public function test_api_key_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = ApiKey::create([
            'name' => 'Test Key', 'key_hash' => hash('sha256', 'test_key'), 'key_prefix' => 'akx_test123',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_api_key_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        ApiKey::create(['name' => 'Key A', 'key_hash' => hash('sha256', 'a'), 'key_prefix' => 'akx_aaaaaaa']);

        CompanyContext::override($this->companyB->id);
        ApiKey::create(['name' => 'Key B', 'key_hash' => hash('sha256', 'b'), 'key_prefix' => 'akx_bbbbbbb']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, ApiKey::forCompany()->get());
    }

    // ── IntegrationProvider ──

    public function test_integration_provider_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = IntegrationProvider::create([
            'provider_code' => 'SAP', 'provider_name' => 'SAP Integration', 'provider_type' => 'ERP',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_integration_provider_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        IntegrationProvider::create(['provider_code' => 'PRV_A', 'provider_name' => 'A', 'provider_type' => 'ERP']);

        CompanyContext::override($this->companyB->id);
        IntegrationProvider::create(['provider_code' => 'PRV_B', 'provider_name' => 'B', 'provider_type' => 'CRM']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, IntegrationProvider::forCompany()->get());
    }

    // ── IntegrationEvent ──

    public function test_integration_event_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = IntegrationEvent::create([
            'event_code' => 'INV_CREATED', 'event_name' => 'Invoice Created',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_integration_event_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        IntegrationEvent::create(['event_code' => 'EVT_A', 'event_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        IntegrationEvent::create(['event_code' => 'EVT_B', 'event_name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, IntegrationEvent::forCompany()->get());
    }

    // ── WebhookEndpoint ──

    public function test_webhook_endpoint_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = WebhookEndpoint::create([
            'webhook_name' => 'Test Endpoint', 'target_url' => 'https://example.com/hook',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_webhook_endpoint_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        WebhookEndpoint::create(['webhook_name' => 'EP A', 'target_url' => 'https://a.com']);

        CompanyContext::override($this->companyB->id);
        WebhookEndpoint::create(['webhook_name' => 'EP B', 'target_url' => 'https://b.com']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, WebhookEndpoint::forCompany()->get());
    }

    // ── Webhook ──

    public function test_webhook_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = Webhook::create([
            'name' => 'Test Webhook', 'url' => 'https://example.com/hook',
            'events' => ['invoice.created'],
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_webhook_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        Webhook::create(['name' => 'WH A', 'url' => 'https://a.com/hook', 'events' => ['a']]);

        CompanyContext::override($this->companyB->id);
        Webhook::create(['name' => 'WH B', 'url' => 'https://b.com/hook', 'events' => ['b']]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, Webhook::forCompany()->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_integrations(): void
    {
        CompanyContext::override($this->companyB->id);
        IntegrationProvider::create(['provider_code' => 'SECRET', 'provider_name' => 'Secret', 'provider_type' => 'X']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(IntegrationProvider::forCompany()->where('provider_code', 'SECRET')->exists());
    }
}
