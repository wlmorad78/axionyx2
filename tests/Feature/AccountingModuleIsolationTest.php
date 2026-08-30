<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\OpeningBalance;
use App\Models\Company;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'ACC-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'ACC-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── FiscalYear ──

    public function test_fiscal_year_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = FiscalYear::create([
            'year_code' => 'FY2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_fiscal_year_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        FiscalYear::create(['year_code' => 'FYA', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

        CompanyContext::override($this->companyB->id);
        FiscalYear::create(['year_code' => 'FYB', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, FiscalYear::forCompany()->get());
    }

    // ── Account ──

    public function test_account_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = Account::create([
            'account_code' => '1001', 'account_name' => 'Cash',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_account_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        Account::create(['account_code' => '1001', 'account_name' => 'Cash A']);

        CompanyContext::override($this->companyB->id);
        Account::create(['account_code' => '1001', 'account_name' => 'Cash B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, Account::forCompany()->get());
    }

    // ── Budget ──

    public function test_budget_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $fy = FiscalYear::create([
            'year_code' => 'FY26A', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);
        $model = Budget::create([
            'budget_code' => 'BUD01', 'budget_name' => 'Budget 2026', 'fiscal_year_id' => $fy->id,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_budget_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $fyA = FiscalYear::create(['year_code' => 'FYAA', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Budget::create(['budget_code' => 'BA', 'budget_name' => 'A', 'fiscal_year_id' => $fyA->id]);

        CompanyContext::override($this->companyB->id);
        $fyB = FiscalYear::create(['year_code' => 'FYBB', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Budget::create(['budget_code' => 'BB', 'budget_name' => 'B', 'fiscal_year_id' => $fyB->id]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, Budget::forCompany()->get());
    }

    // ── OpeningBalance ──

    public function test_opening_balance_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $fy = FiscalYear::create([
            'year_code' => 'OBYA', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ]);
        $acc = Account::create(['account_code' => 'OBA', 'account_name' => 'OB Account']);
        $model = OpeningBalance::create([
            'fiscal_year_id' => $fy->id, 'account_id' => $acc->id,
            'opening_debit' => 1000.00, 'opening_credit' => 0.00,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_opening_balance_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $fyA = FiscalYear::create(['year_code' => 'OBYAA', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $accA = Account::create(['account_code' => 'OBAA', 'account_name' => 'A']);
        OpeningBalance::create(['fiscal_year_id' => $fyA->id, 'account_id' => $accA->id, 'opening_debit' => 100, 'opening_credit' => 0]);

        CompanyContext::override($this->companyB->id);
        $fyB = FiscalYear::create(['year_code' => 'OBYBB', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $accB = Account::create(['account_code' => 'OBBB', 'account_name' => 'B']);
        OpeningBalance::create(['fiscal_year_id' => $fyB->id, 'account_id' => $accB->id, 'opening_debit' => 200, 'opening_credit' => 0]);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, OpeningBalance::forCompany()->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_accounts(): void
    {
        CompanyContext::override($this->companyB->id);
        Account::create(['account_code' => '9999', 'account_name' => 'Secret']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(Account::forCompany()->where('account_code', '9999')->exists());
    }
}
