<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Workflows\MasterDataWorkflow;
use App\Models\Workflows\Workflow;
use App\Models\Workflows\WorkflowDefinition;
use App\Models\Workflows\WorkflowTemplate;
use App\Models\Workflows\WorkflowType;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowsModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'WFL-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'WFL-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── WorkflowType ──

    public function test_workflow_type_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = WorkflowType::create([
            'workflow_code' => 'SALES', 'workflow_name' => 'Sales Approval',
            'entity_type' => 'SalesOrder',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_workflow_type_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        WorkflowType::create(['workflow_code' => 'WF_A', 'workflow_name' => 'A', 'entity_type' => 'A']);

        CompanyContext::override($this->companyB->id);
        WorkflowType::create(['workflow_code' => 'WF_B', 'workflow_name' => 'B', 'entity_type' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, WorkflowType::forCompany()->get());
    }

    // ── WorkflowDefinition ──

    public function test_workflow_definition_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = WorkflowDefinition::create([
            'workflow_code' => 'PO_APPROVE', 'workflow_name' => 'PO Approval',
            'module_name' => 'Purchase',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_workflow_definition_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        WorkflowDefinition::create(['workflow_code' => 'WD_A', 'workflow_name' => 'A', 'module_name' => 'M']);

        CompanyContext::override($this->companyB->id);
        WorkflowDefinition::create(['workflow_code' => 'WD_B', 'workflow_name' => 'B', 'module_name' => 'M']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, WorkflowDefinition::forCompany()->get());
    }

    // ── WorkflowTemplate ──

    public function test_workflow_template_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = WorkflowTemplate::create([
            'template_name' => 'Default Approval', 'entity_type' => 'Invoice',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_workflow_template_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        WorkflowTemplate::create(['template_name' => 'Tmpl A', 'entity_type' => 'A']);

        CompanyContext::override($this->companyB->id);
        WorkflowTemplate::create(['template_name' => 'Tmpl B', 'entity_type' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, WorkflowTemplate::forCompany()->get());
    }

    // ── Workflow ──

    public function test_workflow_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $type = WorkflowType::create([
            'workflow_code' => 'INV', 'workflow_name' => 'Invoice', 'entity_type' => 'Invoice',
        ]);
        $model = Workflow::create([
            'workflow_type_id' => $type->id, 'workflow_name' => 'Invoice Flow', 'status' => 'ACTIVE',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_workflow_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $typeA = WorkflowType::create(['workflow_code' => 'WA', 'workflow_name' => 'A', 'entity_type' => 'A']);
        Workflow::create(['workflow_type_id' => $typeA->id, 'workflow_name' => 'Flow A', 'status' => 'ACTIVE']);

        CompanyContext::override($this->companyB->id);
        $typeB = WorkflowType::create(['workflow_code' => 'WB', 'workflow_name' => 'B', 'entity_type' => 'B']);
        Workflow::create(['workflow_type_id' => $typeB->id, 'workflow_name' => 'Flow B', 'status' => 'ACTIVE']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, Workflow::forCompany()->get());
    }

    // ── MasterDataWorkflow ──

    public function test_master_data_workflow_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = MasterDataWorkflow::create([
            'workflow_name' => 'Item Approval', 'entity_type' => 'Item',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_master_data_workflow_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        MasterDataWorkflow::create(['workflow_name' => 'MD A', 'entity_type' => 'A']);

        CompanyContext::override($this->companyB->id);
        MasterDataWorkflow::create(['workflow_name' => 'MD B', 'entity_type' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, MasterDataWorkflow::forCompany()->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_workflows(): void
    {
        CompanyContext::override($this->companyB->id);
        WorkflowType::create(['workflow_code' => 'SECRET', 'workflow_name' => 'Secret', 'entity_type' => 'X']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(WorkflowType::forCompany()->where('workflow_code', 'SECRET')->exists());
    }
}
