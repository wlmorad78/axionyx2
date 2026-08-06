<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Tax\TaxExemption;
use App\Models\Tax\TaxGroup;
use App\Models\Tax\TaxPeriod;
use App\Models\Tax\TaxReturn;
use App\Models\Tax\TaxRule;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'TAX-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'TAX-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── TaxGroup ──

    public function test_tax_group_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = TaxGroup::create([
            'group_code' => 'TG-001', 'group_name' => 'Test Group',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_tax_group_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        TaxGroup::create(['group_code' => 'TG-A', 'group_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        TaxGroup::create(['group_code' => 'TG-B', 'group_name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, TaxGroup::forCompany()->get());
        $this->assertEquals('TG-A', TaxGroup::forCompany()->first()->group_code);
    }

    // ── TaxExemption ──

    public function test_tax_exemption_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = TaxExemption::create([
            'exemption_code' => 'EX-001', 'exemption_name' => 'Test',
            'effective_from' => '2026-01-01',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_tax_exemption_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        TaxExemption::create(['exemption_code' => 'EX-A', 'exemption_name' => 'A', 'effective_from' => '2026-01-01']);

        CompanyContext::override($this->companyB->id);
        TaxExemption::create(['exemption_code' => 'EX-B', 'exemption_name' => 'B', 'effective_from' => '2026-01-01']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, TaxExemption::forCompany()->get());
    }

    // ── TaxPeriod ──

    public function test_tax_period_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = TaxPeriod::create([
            'period_name' => 'Q1 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_tax_period_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        TaxPeriod::create(['period_name' => 'A', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);

        CompanyContext::override($this->companyB->id);
        TaxPeriod::create(['period_name' => 'B', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, TaxPeriod::forCompany()->get());
    }

    // ── TaxReturn ──

    public function test_tax_return_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $period = TaxPeriod::create([
            'period_name' => 'Q1', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31',
        ]);
        $model = TaxReturn::create([
            'tax_period_id' => $period->id, 'return_no' => 'RET-001',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_tax_return_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $periodA = TaxPeriod::create(['period_name' => 'A', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        TaxReturn::create(['tax_period_id' => $periodA->id, 'return_no' => 'RET-A']);

        CompanyContext::override($this->companyB->id);
        $periodB = TaxPeriod::create(['period_name' => 'B', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        TaxReturn::create(['tax_period_id' => $periodB->id, 'return_no' => 'RET-B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, TaxReturn::forCompany()->get());
    }

    // ── TaxRule ──

    public function test_tax_rule_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $group = TaxGroup::create(['group_code' => 'TG-R', 'group_name' => 'Rule Group']);
        $model = TaxRule::create([
            'rule_name' => 'Test Rule', 'tax_group_id' => $group->id,
            'effective_from' => '2026-01-01', 'priority' => 1,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_tax_rule_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $gA = TaxGroup::create(['group_code' => 'TG-RA', 'group_name' => 'A']);
        TaxRule::create(['rule_name' => 'A', 'tax_group_id' => $gA->id, 'effective_from' => '2026-01-01']);

        CompanyContext::override($this->companyB->id);
        $gB = TaxGroup::create(['group_code' => 'TG-RB', 'group_name' => 'B']);
        TaxRule::create(['rule_name' => 'B', 'tax_group_id' => $gB->id, 'effective_from' => '2026-01-01']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, TaxRule::forCompany()->get());
    }

    // ── Cross-company access denial ──

    public function test_company_a_cannot_see_company_b_tax_groups(): void
    {
        CompanyContext::override($this->companyB->id);
        TaxGroup::create(['group_code' => 'SECRET', 'group_name' => 'Secret']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(TaxGroup::forCompany()->where('group_code', 'SECRET')->exists());
    }

    public function test_company_a_cannot_see_company_b_tax_exemptions(): void
    {
        CompanyContext::override($this->companyB->id);
        TaxExemption::create(['exemption_code' => 'SECRET', 'exemption_name' => 'Secret', 'effective_from' => '2026-01-01']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(TaxExemption::forCompany()->where('exemption_code', 'SECRET')->exists());
    }

    public function test_company_a_cannot_see_company_b_tax_periods(): void
    {
        CompanyContext::override($this->companyB->id);
        TaxPeriod::create(['period_name' => 'Secret', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(TaxPeriod::forCompany()->where('period_name', 'Secret')->exists());
    }

    public function test_company_a_cannot_see_company_b_tax_returns(): void
    {
        CompanyContext::override($this->companyB->id);
        $p = TaxPeriod::create(['period_name' => 'S', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        TaxReturn::create(['tax_period_id' => $p->id, 'return_no' => 'SECRET']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(TaxReturn::forCompany()->where('return_no', 'SECRET')->exists());
    }

    public function test_company_a_cannot_see_company_b_tax_rules(): void
    {
        CompanyContext::override($this->companyB->id);
        $g = TaxGroup::create(['group_code' => 'TG-S', 'group_name' => 'S']);
        TaxRule::create(['rule_name' => 'Secret', 'tax_group_id' => $g->id, 'effective_from' => '2026-01-01']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(TaxRule::forCompany()->where('rule_name', 'Secret')->exists());
    }

    // ── Null context returns all ──

    public function test_null_context_returns_all_tax_groups(): void
    {
        CompanyContext::override($this->companyA->id);
        TaxGroup::create(['group_code' => 'A1', 'group_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        TaxGroup::create(['group_code' => 'B1', 'group_name' => 'B']);

        CompanyContext::clear();
        $this->assertCount(2, TaxGroup::forCompany()->get());
    }

    // ── Explicit company_id override ──

    public function test_explicit_company_id_overrides_null_context(): void
    {
        CompanyContext::override($this->companyA->id);
        TaxGroup::create(['group_code' => 'EA', 'group_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        TaxGroup::create(['group_code' => 'EB', 'group_name' => 'B']);

        CompanyContext::clear();
        $results = TaxGroup::forCompany($this->companyA->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('EA', $results->first()->group_code);
    }

    // ── Tax Rule with TaxGroup relationship isolation ──

    public function test_tax_rule_with_tax_group_relationship(): void
    {
        CompanyContext::override($this->companyA->id);
        $group = TaxGroup::create(['group_code' => 'TG-REL', 'group_name' => 'Rel']);
        $rule = TaxRule::create([
            'rule_name' => 'Rel Rule', 'tax_group_id' => $group->id,
            'effective_from' => '2026-01-01',
        ]);

        $this->assertNotNull($rule->taxGroup);
        $this->assertEquals($group->id, $rule->taxGroup->id);
    }

    // ── Tax Return with TaxPeriod relationship isolation ──

    public function test_tax_return_with_tax_period_relationship(): void
    {
        CompanyContext::override($this->companyA->id);
        $period = TaxPeriod::create([
            'period_name' => 'Rel Period', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31',
        ]);
        $return = TaxReturn::create([
            'tax_period_id' => $period->id, 'return_no' => 'RET-REL',
        ]);

        $this->assertNotNull($return->taxPeriod);
        $this->assertEquals($period->id, $return->taxPeriod->id);
    }

    // ── Company relationship still works ──

    public function test_company_relationship_still_works(): void
    {
        CompanyContext::override($this->companyA->id);
        $group = TaxGroup::create(['group_code' => 'TG-CR', 'group_name' => 'CR']);
        $this->assertNotNull($group->company_id);
        $this->assertEquals($this->companyA->id, $group->company_id);
    }

    // ── Soft deletes still work ──

    public function test_soft_deletes_still_work(): void
    {
        CompanyContext::override($this->companyA->id);
        $group = TaxGroup::create(['group_code' => 'TG-SD', 'group_name' => 'SD']);
        $id = $group->id;
        $group->delete();

        $this->assertSoftDeleted('tax_groups', ['id' => $id]);
        $this->assertNotNull(TaxGroup::withTrashed()->find($id));
    }
}
