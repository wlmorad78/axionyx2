<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Settings\ActivityLog;
use App\Models\Settings\AuditLog;
use App\Models\Settings\CompanySetting;
use App\Models\Settings\CompanySubscription;
use App\Models\Settings\ExternalDocument;
use App\Models\Settings\NumberSeries;
use App\Models\Settings\SubscriptionPlan;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'SET-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'SET-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
        $this->plan = SubscriptionPlan::create([
            'code' => 'BASIC', 'name' => 'Basic Plan', 'duration_months' => 12, 'price' => 99.00,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── ActivityLog ──

    public function test_activity_log_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = ActivityLog::create([
            'type' => 'test', 'description' => 'Test log',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_activity_log_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        ActivityLog::create(['type' => 'a', 'description' => 'A']);

        CompanyContext::override($this->companyB->id);
        ActivityLog::create(['type' => 'b', 'description' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, ActivityLog::forCompany()->get());
    }

    // ── AuditLog ──

    public function test_audit_log_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = AuditLog::create([
            'table_name' => 'users', 'record_id' => 1, 'action_type' => 'CREATE',
            'created_at' => now(),
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_audit_log_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        AuditLog::create(['table_name' => 'a', 'record_id' => 1, 'action_type' => 'CREATE', 'created_at' => now()]);

        CompanyContext::override($this->companyB->id);
        AuditLog::create(['table_name' => 'b', 'record_id' => 1, 'action_type' => 'CREATE', 'created_at' => now()]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, AuditLog::forCompany()->get());
    }

    // ── CompanySetting ──

    public function test_company_setting_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = CompanySetting::create([
            'group' => 'general', 'key' => 'name', 'value' => 'Test', 'type' => 'string',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_company_setting_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        CompanySetting::create(['group' => 'g', 'key' => 'a', 'value' => 'A']);

        CompanyContext::override($this->companyB->id);
        CompanySetting::create(['group' => 'g', 'key' => 'b', 'value' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, CompanySetting::forCompany()->get());
    }

    // ── CompanySubscription ──

    public function test_company_subscription_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = CompanySubscription::create([
            'subscription_plan_id' => $this->plan->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'amount' => 99.00,
            'status' => 'ACTIVE',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_company_subscription_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        CompanySubscription::create([
            'subscription_plan_id' => $this->plan->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'amount' => 99.00,
            'status' => 'ACTIVE',
        ]);

        CompanyContext::override($this->companyB->id);
        CompanySubscription::create([
            'subscription_plan_id' => $this->plan->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'amount' => 99.00,
            'status' => 'ACTIVE',
        ]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, CompanySubscription::forCompany()->get());
    }

    // ── ExternalDocument ──

    public function test_external_document_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = ExternalDocument::create([
            'external_document_no' => 'EXT-001', 'entity_type' => 'Invoice', 'status' => 'PENDING',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_external_document_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        ExternalDocument::create(['external_document_no' => 'A', 'entity_type' => 'Invoice', 'status' => 'PENDING']);

        CompanyContext::override($this->companyB->id);
        ExternalDocument::create(['external_document_no' => 'B', 'entity_type' => 'Invoice', 'status' => 'PENDING']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, ExternalDocument::forCompany()->get());
    }

    // ── NumberSeries ──

    public function test_number_series_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = NumberSeries::create([
            'document_type' => 'SALES_INVOICE', 'prefix' => 'SI', 'format' => '{prefix}-{sequence}',
            'next_sequence' => 1, 'padding' => 5,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_number_series_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        NumberSeries::create(['document_type' => 'SI', 'prefix' => 'SI', 'format' => '{prefix}-{sequence}', 'next_sequence' => 1, 'padding' => 5]);

        CompanyContext::override($this->companyB->id);
        NumberSeries::create(['document_type' => 'SI', 'prefix' => 'SI', 'format' => '{prefix}-{sequence}', 'next_sequence' => 1, 'padding' => 5]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, NumberSeries::forCompany()->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_settings(): void
    {
        CompanyContext::override($this->companyB->id);
        CompanySetting::create(['group' => 'secret', 'key' => 'api_key', 'value' => 'xxx']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(CompanySetting::forCompany()->where('key', 'api_key')->exists());
    }
}
