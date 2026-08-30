<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PriceLevel;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'PR-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'PR-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    public function test_price_level_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = PriceLevel::create([
            'level_code' => 'PL-001', 'level_name' => 'Gold', 'priority' => 1,
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_price_level_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        PriceLevel::create(['level_code' => 'PL-A', 'level_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        PriceLevel::create(['level_code' => 'PL-B', 'level_name' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, PriceLevel::forCompany()->get());
        $this->assertEquals('PL-A', PriceLevel::forCompany()->first()->level_code);
    }

    public function test_company_a_cannot_see_company_b_price_levels(): void
    {
        CompanyContext::override($this->companyB->id);
        PriceLevel::create(['level_code' => 'SECRET', 'level_name' => 'Secret']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(PriceLevel::forCompany()->where('level_code', 'SECRET')->exists());
    }

    public function test_null_context_returns_all_price_levels(): void
    {
        CompanyContext::override($this->companyA->id);
        PriceLevel::create(['level_code' => 'A', 'level_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        PriceLevel::create(['level_code' => 'B', 'level_name' => 'B']);

        CompanyContext::clear();
        $this->assertCount(2, PriceLevel::forCompany()->get());
    }

    public function test_explicit_company_id_overrides_null_context(): void
    {
        CompanyContext::override($this->companyA->id);
        PriceLevel::create(['level_code' => 'EA', 'level_name' => 'A']);

        CompanyContext::override($this->companyB->id);
        PriceLevel::create(['level_code' => 'EB', 'level_name' => 'B']);

        CompanyContext::clear();
        $results = PriceLevel::forCompany($this->companyA->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('EA', $results->first()->level_code);
    }

    public function test_company_relationship(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = PriceLevel::create(['level_code' => 'PL-CR', 'level_name' => 'CR']);
        $this->assertNotNull($model->company_id);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_soft_deletes(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = PriceLevel::create(['level_code' => 'PL-SD', 'level_name' => 'SD']);
        $id = $model->id;
        $model->delete();

        $this->assertSoftDeleted('price_levels', ['id' => $id]);
        $this->assertNotNull(PriceLevel::withTrashed()->find($id));
    }
}
