<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierGroup;
use App\Models\SupplierQuotation;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuppliersModuleIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'SUP-A', 'name_ar' => 'أ', 'name_en' => 'A', 'is_active' => true,
        ]);
        $this->companyB = Company::create([
            'code' => 'SUP-B', 'name_ar' => 'ب', 'name_en' => 'B', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── Supplier (already has trait) ──

    public function test_supplier_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = Supplier::create(['supplier_name' => 'Acme Corp']);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_supplier_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        Supplier::create(['supplier_name' => 'Supplier A']);

        CompanyContext::override($this->companyB->id);
        Supplier::create(['supplier_name' => 'Supplier B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, Supplier::forCompany()->get());
    }

    // ── SupplierGroup (already has trait) ──

    public function test_supplier_group_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $model = SupplierGroup::create(['code' => 'GRP1', 'name_ar' => 'Group 1']);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_supplier_group_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        SupplierGroup::create(['code' => 'SGA', 'name_ar' => 'A']);

        CompanyContext::override($this->companyB->id);
        SupplierGroup::create(['code' => 'SGB', 'name_ar' => 'B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, SupplierGroup::forCompany()->get());
    }

    // ── SupplierQuotation (trait just added) ──

    public function test_supplier_quotation_auto_sets_company_id(): void
    {
        CompanyContext::override($this->companyA->id);
        $supplier = Supplier::create(['supplier_name' => 'Test Supplier']);
        $model = SupplierQuotation::create([
            'supplier_id' => $supplier->id,
            'quotation_date' => '2026-01-15',
        ]);
        $this->assertEquals($this->companyA->id, $model->company_id);
    }

    public function test_supplier_quotation_isolation(): void
    {
        CompanyContext::override($this->companyA->id);
        $sA = Supplier::create(['supplier_name' => 'S A']);
        SupplierQuotation::create(['supplier_id' => $sA->id, 'quotation_date' => '2026-01-01']);

        CompanyContext::override($this->companyB->id);
        $sB = Supplier::create(['supplier_name' => 'S B']);
        SupplierQuotation::create(['supplier_id' => $sB->id, 'quotation_date' => '2026-01-01']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, SupplierQuotation::forCompany()->get());
    }

    // ── SupplierContact (child of Supplier — inherited) ──

    public function test_supplier_contact_isolation_via_parent(): void
    {
        CompanyContext::override($this->companyA->id);
        $sA = Supplier::create(['supplier_name' => 'SCA']);
        $sA->contacts()->create(['contact_name' => 'Contact A']);

        CompanyContext::override($this->companyB->id);
        $sB = Supplier::create(['supplier_name' => 'SCB']);
        $sB->contacts()->create(['contact_name' => 'Contact B']);

        CompanyContext::override($this->companyA->id);
        $this->assertCount(1, \App\Models\SupplierContact::whereIn('supplier_id', Supplier::forCompany()->pluck('id'))->get());
    }

    // ── Cross-company ──

    public function test_company_a_cannot_see_company_b_suppliers(): void
    {
        CompanyContext::override($this->companyB->id);
        Supplier::create(['supplier_name' => 'Secret Supplier']);

        CompanyContext::override($this->companyA->id);
        $this->assertFalse(Supplier::forCompany()->where('supplier_name', 'Secret Supplier')->exists());
    }
}
