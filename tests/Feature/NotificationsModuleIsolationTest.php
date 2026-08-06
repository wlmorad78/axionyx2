<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Notifications\AlertRule;
use App\Models\Notifications\NotificationEvent;
use App\Models\Notifications\NotificationGroup;
use App\Models\Notifications\NotificationTemplate;
use App\Models\Notifications\NotificationType;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'NTF-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'NTF-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── NotificationType ──

    public function test_notification_type_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = NotificationType::create([
            'type_code' => 'EMAIL', 'type_name' => 'Email Notifications',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_notification_type_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        NotificationType::create(['type_code' => 'TYPE_A', 'type_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        NotificationType::create(['type_code' => 'TYPE_B', 'type_name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, NotificationType::forCompany()->get());
    }

    // ── NotificationEvent ──

    public function test_notification_event_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = NotificationEvent::create([
            'event_code' => 'INV_CREATED', 'event_name' => 'Invoice Created',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_notification_event_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        NotificationEvent::create(['event_code' => 'EVT_A', 'event_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        NotificationEvent::create(['event_code' => 'EVT_B', 'event_name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, NotificationEvent::forCompany()->get());
    }

    // ── AlertRule ──

    public function test_alert_rule_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = AlertRule::create([
            'alert_code' => 'LOW_STOCK', 'alert_name' => 'Low Stock',
            'condition_expression' => 'qty < 10',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_alert_rule_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        AlertRule::create(['alert_code' => 'RULE_A', 'alert_name' => 'A', 'condition_expression' => 'x']);

        CompanyContext::override($this->companyB->id);
        AlertRule::create(['alert_code' => 'RULE_B', 'alert_name' => 'B', 'condition_expression' => 'y']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, AlertRule::forCompany()->get());
    }

    // ── NotificationGroup ──

    public function test_notification_group_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = NotificationGroup::create([
            'group_code' => 'ADMINS', 'group_name' => 'Administrators',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_notification_group_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        NotificationGroup::create(['group_code' => 'GRP_A', 'group_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        NotificationGroup::create(['group_code' => 'GRP_B', 'group_name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, NotificationGroup::forCompany()->get());
    }

    // ── NotificationTemplate ──

    public function test_notification_template_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = NotificationTemplate::create([
            'template_code' => 'INV_TMPL', 'template_name' => 'Invoice Template',
            'title' => 'Invoice', 'message_body' => 'Your invoice is ready', 'channel' => 'email',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_notification_template_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        NotificationTemplate::create(['template_code' => 'TMPL_A', 'template_name' => 'A', 'title' => 'A', 'message_body' => 'A', 'channel' => 'email']);

        CompanyContext::override($this->companyB->id);
        NotificationTemplate::create(['template_code' => 'TMPL_B', 'template_name' => 'B', 'title' => 'B', 'message_body' => 'B', 'channel' => 'email']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, NotificationTemplate::forCompany()->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_notifications(): void
    {
        CompanyContext::override($this->companyB->id);
        NotificationType::create(['type_code' => 'SECRET', 'type_name' => 'Secret']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(NotificationType::forCompany()->where('type_code', 'SECRET')->exists());
    }
}
