<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Tax\TaxType;
use App\Models\Inventory\ItemCategory;
use App\Models\CRM\CustomerType;
use App\Models\HR\Holiday;
use App\Models\Pricing\PricingMethod;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToCompanyPilotTest extends TestCase
{
    use RefreshDatabase;

    private int $companyA;
    private int $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create([
            'code' => 'PILOT-A',
            'name_ar' => 'شركة أ',
            'name_en' => 'Company A',
            'is_active' => true,
        ])->id;

        $this->companyB = Company::create([
            'code' => 'PILOT-B',
            'name_ar' => 'شركة ب',
            'name_en' => 'Company B',
            'is_active' => true,
        ])->id;
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── A. Automatic company_id assignment on create ──

    public function test_tax_type_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA);
        $model = TaxType::create([
            'tax_code' => 'PILOT-TT-001',
            'tax_name' => 'Pilot Tax Type',
            'tax_category' => 'VAT',
            'is_active' => true,
        ]);

        $this->assertEquals($this->companyA, $model->company_id);
    }

    public function test_item_category_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA);
        $model = ItemCategory::create([
            'code' => 'PILOT-IC-001',
            'name_ar' => 'تصنيف تجريبي',
            'name_en' => 'Pilot Category',
            'is_active' => true,
        ]);

        $this->assertEquals($this->companyA, $model->company_id);
    }

    public function test_customer_type_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA);
        $model = CustomerType::create([
            'code' => 'PILOT-CT-001',
            'name_ar' => 'نوع تجريبي',
            'name_en' => 'Pilot Type',
            'is_active' => true,
        ]);

        $this->assertEquals($this->companyA, $model->company_id);
    }

    public function test_holiday_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA);
        $model = Holiday::create([
            'name_ar' => 'عطلة تجريبية',
            'name_en' => 'Pilot Holiday',
            'holiday_date' => '2026-01-01',
            'is_paid' => true,
        ]);

        $this->assertEquals($this->companyA, $model->company_id);
    }

    public function test_pricing_method_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA);
        $model = PricingMethod::create([
            'method_code' => 'PILOT-PM-001',
            'method_name' => 'Pilot Method',
            'is_active' => true,
        ]);

        $this->assertEquals($this->companyA, $model->company_id);
    }

    // ── B. Query filtering by company ──

    public function test_tax_type_filters_by_company(): void
    {
        CompanyContext::override($this->companyA);
        TaxType::create(['tax_code' => 'FILTER-A-001', 'tax_name' => 'A', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        TaxType::create(['tax_code' => 'FILTER-B-001', 'tax_name' => 'B', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyA);
        $results = TaxType::forCompany()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('FILTER-A-001', $results->first()->tax_code);
    }

    public function test_item_category_filters_by_company(): void
    {
        CompanyContext::override($this->companyA);
        ItemCategory::create(['code' => 'FILTER-A-IC', 'name_ar' => 'A', 'name_en' => 'A', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        ItemCategory::create(['code' => 'FILTER-B-IC', 'name_ar' => 'B', 'name_en' => 'B', 'is_active' => true]);

        CompanyContext::override($this->companyA);
        $results = ItemCategory::forCompany()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('FILTER-A-IC', $results->first()->code);
    }

    // ── C & D. Cross-company isolation ──

    public function test_company_a_cannot_see_company_b_tax_types(): void
    {
        CompanyContext::override($this->companyB);
        TaxType::create(['tax_code' => 'SECRET-B', 'tax_name' => 'Secret', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyA);
        $results = TaxType::forCompany()->get();

        $this->assertFalse($results->contains('tax_code', 'SECRET-B'));
    }

    public function test_company_b_cannot_see_company_a_item_categories(): void
    {
        CompanyContext::override($this->companyA);
        ItemCategory::create(['code' => 'SECRET-A', 'name_ar' => 'A', 'name_en' => 'Secret A', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        $results = ItemCategory::forCompany()->get();

        $this->assertFalse($results->contains('code', 'SECRET-A'));
    }

    public function test_company_a_cannot_see_company_b_customer_types(): void
    {
        CompanyContext::override($this->companyB);
        CustomerType::create(['code' => 'SECRET-CT-B', 'name_ar' => 'B', 'name_en' => 'Secret B', 'is_active' => true]);

        CompanyContext::override($this->companyA);
        $results = CustomerType::forCompany()->get();

        $this->assertFalse($results->contains('code', 'SECRET-CT-B'));
    }

    public function test_company_a_cannot_see_company_b_holidays(): void
    {
        CompanyContext::override($this->companyB);
        Holiday::create(['name_ar' => 'B', 'name_en' => 'Secret B', 'holiday_date' => '2026-06-01', 'is_paid' => true]);

        CompanyContext::override($this->companyA);
        $results = Holiday::forCompany()->get();

        $this->assertFalse($results->contains('name_en', 'Secret B'));
    }

    public function test_company_a_cannot_see_company_b_pricing_methods(): void
    {
        CompanyContext::override($this->companyB);
        PricingMethod::create(['method_code' => 'SECRET-PM-B', 'method_name' => 'Secret B', 'is_active' => true]);

        CompanyContext::override($this->companyA);
        $results = PricingMethod::forCompany()->get();

        $this->assertFalse($results->contains('method_code', 'SECRET-PM-B'));
    }

    // ── E. Update/delete operations remain company-safe ──

    public function test_update_respects_company_scope(): void
    {
        CompanyContext::override($this->companyA);
        $model = TaxType::create(['tax_code' => 'UPDATE-TEST', 'tax_name' => 'Original', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        $found = TaxType::forCompany()->find($model->id);

        $this->assertNull($found);
    }

    public function test_delete_respects_company_scope(): void
    {
        CompanyContext::override($this->companyA);
        $model = TaxType::create(['tax_code' => 'DELETE-TEST', 'tax_name' => 'To Delete', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        TaxType::forCompany()->where('id', $model->id)->delete();

        CompanyContext::override($this->companyA);
        $this->assertDatabaseHas('tax_types', ['id' => $model->id, 'deleted_at' => null]);
    }

    // ── F. Behavior when CompanyContext::id() is null ──

    public function test_null_context_returns_all_records(): void
    {
        CompanyContext::override($this->companyA);
        TaxType::create(['tax_code' => 'NULL-A', 'tax_name' => 'A', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        TaxType::create(['tax_code' => 'NULL-B', 'tax_name' => 'B', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::clear();
        $results = TaxType::forCompany()->get();

        $this->assertCount(2, $results);
    }

    public function test_explicit_company_id_overrides_null_context(): void
    {
        CompanyContext::override($this->companyA);
        TaxType::create(['tax_code' => 'EXPLICIT-A', 'tax_name' => 'A', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::override($this->companyB);
        TaxType::create(['tax_code' => 'EXPLICIT-B', 'tax_name' => 'B', 'tax_category' => 'VAT', 'is_active' => true]);

        CompanyContext::clear();
        $results = TaxType::forCompany($this->companyA)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('EXPLICIT-A', $results->first()->tax_code);
    }

    // ── G. Existing functionality remains unchanged ──

    public function test_explicit_company_id_on_create_overrides_context(): void
    {
        CompanyContext::override($this->companyA);
        $model = TaxType::create([
            'tax_code' => 'OVERRIDE-TEST',
            'tax_name' => 'Override',
            'company_id' => $this->companyB,
            'tax_category' => 'VAT',
            'is_active' => true,
        ]);

        $this->assertEquals($this->companyB, $model->company_id);
    }

    public function test_soft_deletes_still_work_with_trait(): void
    {
        CompanyContext::override($this->companyA);
        $model = TaxType::create(['tax_code' => 'SOFT-DEL', 'tax_name' => 'Soft Delete', 'tax_category' => 'VAT', 'is_active' => true]);
        $id = $model->id;

        $model->delete();

        $this->assertSoftDeleted('tax_types', ['id' => $id]);

        $trashed = TaxType::withTrashed()->find($id);
        $this->assertNotNull($trashed);
    }

    public function test_company_relationship_still_works(): void
    {
        CompanyContext::override($this->companyA);
        $model = TaxType::create(['tax_code' => 'REL-TEST', 'tax_name' => 'Relationship', 'tax_category' => 'VAT', 'is_active' => true]);

        $this->assertNotNull($model->company_id);
        $this->assertEquals($this->companyA, $model->company_id);

        $company = \App\Models\Company\Company::find($this->companyA);
        $this->assertEquals('PILOT-A', $company->code);
    }
}
