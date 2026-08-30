<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobGrade;
use App\Models\JobFamily;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\SalaryComponentType;
use App\Models\SalaryScale;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HRModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'HR-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'HR-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── Department ──

    public function test_department_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = Department::create(['code' => 'IT', 'name' => 'IT Department']);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_department_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        Department::create(['code' => 'DPT_A', 'name' => 'A']);

        CompanyContext::override($this->companyB->id);
        Department::create(['code' => 'DPT_B', 'name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, Department::forCompany()->get());
    }

    // ── JobGrade ──

    public function test_job_grade_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = JobGrade::create(['code' => 'G1', 'name_ar' => 'Grade 1']);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_job_grade_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        JobGrade::create(['code' => 'JGA', 'name_ar' => 'A']);

        CompanyContext::override($this->companyB->id);
        JobGrade::create(['code' => 'JGB', 'name_ar' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, JobGrade::forCompany()->get());
    }

    // ── JobFamily ──

    public function test_job_family_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = JobFamily::create(['code' => 'ENG', 'name_ar' => 'Engineering']);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_job_family_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        JobFamily::create(['code' => 'JFA', 'name_ar' => 'A']);

        CompanyContext::override($this->companyB->id);
        JobFamily::create(['code' => 'JFB', 'name_ar' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, JobFamily::forCompany()->get());
    }

    // ── PayrollPeriod ──

    public function test_payroll_period_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = PayrollPeriod::create([
            'period_name' => 'Jan 2026', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_payroll_period_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        PayrollPeriod::create(['period_name' => 'PPA', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        CompanyContext::override($this->companyB->id);
        PayrollPeriod::create(['period_name' => 'PPB', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, PayrollPeriod::forCompany()->get());
    }

    // ── SalaryScale ──

    public function test_salary_scale_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $grade = JobGrade::create(['code' => 'SCG', 'name_ar' => 'Scale Grade']);
        $model = SalaryScale::create([
            'code' => 'SC1', 'name_ar' => 'Scale 1', 'job_grade_id' => $grade->id,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_salary_scale_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $gA = JobGrade::create(['code' => 'SSGA', 'name_ar' => 'A']);
        SalaryScale::create(['code' => 'SSA', 'name_ar' => 'A', 'job_grade_id' => $gA->id]);

        CompanyContext::override($this->companyB->id);
        $gB = JobGrade::create(['code' => 'SSGB', 'name_ar' => 'B']);
        SalaryScale::create(['code' => 'SSB', 'name_ar' => 'B', 'job_grade_id' => $gB->id]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, SalaryScale::forCompany()->get());
    }

    // ── SalaryComponent ──

    public function test_salary_component_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $type = SalaryComponentType::create(['code' => 'BASIC', 'name_ar' => 'Basic']);
        $model = SalaryComponent::create([
            'code' => 'SAL01', 'name_ar' => 'Basic Salary', 'salary_component_type_id' => $type->id,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_salary_component_isolation(): void
    {
        $type = SalaryComponentType::create(['code' => 'COMP', 'name_ar' => 'Component']);

        CompanyContext::override($this->companyA->id);
        SalaryComponent::create(['code' => 'SCA', 'name_ar' => 'A', 'salary_component_type_id' => $type->id]);

        CompanyContext::override($this->companyB->id);
        SalaryComponent::create(['code' => 'SCB', 'name_ar' => 'B', 'salary_component_type_id' => $type->id]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, SalaryComponent::forCompany()->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_hr(): void
    {
        CompanyContext::override($this->companyB->id);
        Department::create(['code' => 'SECRET', 'name' => 'Secret']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(Department::forCompany()->where('code', 'SECRET')->exists());
    }
}
