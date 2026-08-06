<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitType;
use App\Models\OrganizationalLevel;
use Illuminate\Database\Seeder;

class OrganizationUnitSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $types = [
            'DIVISION' => OrganizationUnitType::where('code', 'DIVISION')->first()->id,
            'DEPT' => OrganizationUnitType::where('code', 'DEPT')->first()->id,
            'SECTION' => OrganizationUnitType::where('code', 'SECTION')->first()->id,
            'UNIT' => OrganizationUnitType::where('code', 'UNIT')->first()->id,
            'TEAM' => OrganizationUnitType::where('code', 'TEAM')->first()->id,
        ];

        $levels = [
            'CEO' => OrganizationalLevel::where('code', 'CEO')->first()->id,
            'GM' => OrganizationalLevel::where('code', 'GM')->first()->id,
            'DIRECTOR' => OrganizationalLevel::where('code', 'DIRECTOR')->first()->id,
            'MANAGER' => OrganizationalLevel::where('code', 'MANAGER')->first()->id,
            'SUPERVISOR' => OrganizationalLevel::where('code', 'SUPERVISOR')->first()->id,
            'TEAM_LEADER' => OrganizationalLevel::where('code', 'TEAM_LEADER')->first()->id,
            'EMPLOYEE' => OrganizationalLevel::where('code', 'EMPLOYEE')->first()->id,
        ];

        foreach ($companies as $company) {
            $this->createOrgStructure($company->id, $types, $levels);
        }
    }

    private function createOrgStructure(int $companyId, array $types, array $levels): void
    {
        // ═══════════════════════════════════════════════════════
        // الإدارة العليا
        // ═══════════════════════════════════════════════════════
        $ceo = OrganizationUnit::updateOrCreate(
            ['code' => "CEO-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DIVISION'],
                'parent_id' => null,
                'name_ar' => 'مكتب الرئيس التنفيذي',
                'name_en' => 'CEO Office',
                'organizational_level_id' => $levels['CEO'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // الإدارة العامة
        // ═══════════════════════════════════════════════════════
        $gm = OrganizationUnit::updateOrCreate(
            ['code' => "GM-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DIVISION'],
                'parent_id' => $ceo->id,
                'name_ar' => 'المديرية العامة',
                'name_en' => 'General Management',
                'organizational_level_id' => $levels['GM'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // إدارة الموارد البشرية
        // ═══════════════════════════════════════════════════════
        $hr = OrganizationUnit::updateOrCreate(
            ['code' => "HR-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DEPT'],
                'parent_id' => $gm->id,
                'name_ar' => 'إدارة الموارد البشرية',
                'name_en' => 'Human Resources',
                'organizational_level_id' => $levels['DIRECTOR'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "HR-REC-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $hr->id,
                'name_ar' => 'شعبة التوظيف والاستقطاب',
                'name_en' => 'Recruitment',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "HR-TRAIN-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $hr->id,
                'name_ar' => 'شعبة التدريب والتطوير',
                'name_en' => 'Training & Development',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "HR-ADMIN-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $hr->id,
                'name_ar' => 'شعبة شؤون الموظفين',
                'name_en' => 'Employee Affairs',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // الإدارة المالية
        // ═══════════════════════════════════════════════════════
        $fin = OrganizationUnit::updateOrCreate(
            ['code' => "FIN-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DEPT'],
                'parent_id' => $gm->id,
                'name_ar' => 'الإدارة المالية',
                'name_en' => 'Finance',
                'organizational_level_id' => $levels['DIRECTOR'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "FIN-ACC-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $fin->id,
                'name_ar' => 'شعبة المحاسبة',
                'name_en' => 'Accounting',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "FIN-AR-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $fin->id,
                'name_ar' => 'شعبة الذمم المدينة',
                'name_en' => 'Accounts Receivable',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "FIN-AP-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $fin->id,
                'name_ar' => 'شعبة الذمم الدائنة',
                'name_en' => 'Accounts Payable',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // إدارة المبيعات
        // ═══════════════════════════════════════════════════════
        $sales = OrganizationUnit::updateOrCreate(
            ['code' => "SALES-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DEPT'],
                'parent_id' => $gm->id,
                'name_ar' => 'إدارة المبيعات',
                'name_en' => 'Sales',
                'organizational_level_id' => $levels['DIRECTOR'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "SALES-INSIDE-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $sales->id,
                'name_ar' => 'شعبة المبيعات الداخلية',
                'name_en' => 'Inside Sales',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "SALES-FIELD-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $sales->id,
                'name_ar' => 'شعبة المبيعات الميدانية',
                'name_en' => 'Field Sales',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "SALES-TEAM1-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['TEAM'],
                'parent_id' => $sales->id,
                'name_ar' => 'فريق المبيعات 1',
                'name_en' => 'Sales Team 1',
                'organizational_level_id' => $levels['TEAM_LEADER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "SALES-TEAM2-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['TEAM'],
                'parent_id' => $sales->id,
                'name_ar' => 'فريق المبيعات 2',
                'name_en' => 'Sales Team 2',
                'organizational_level_id' => $levels['TEAM_LEADER'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // إدارة المخازن واللوجستيات
        // ═══════════════════════════════════════════════════════
        $wh = OrganizationUnit::updateOrCreate(
            ['code' => "WH-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DEPT'],
                'parent_id' => $gm->id,
                'name_ar' => 'إدارة المخازن واللوجستيات',
                'name_en' => 'Warehouse & Logistics',
                'organizational_level_id' => $levels['DIRECTOR'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "WH-MAIN-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $wh->id,
                'name_ar' => 'المخزن الرئيسي',
                'name_en' => 'Main Warehouse',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "WH-RETURN-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $wh->id,
                'name_ar' => 'مخزن المرتجعات',
                'name_en' => 'Returns Warehouse',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "WH-INV-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['UNIT'],
                'parent_id' => $wh->id,
                'name_ar' => 'وحدة الجرد والمطابقة',
                'name_en' => 'Inventory Control',
                'organizational_level_id' => $levels['SUPERVISOR'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // تقنية المعلومات
        // ═══════════════════════════════════════════════════════
        $it = OrganizationUnit::updateOrCreate(
            ['code' => "IT-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DEPT'],
                'parent_id' => $gm->id,
                'name_ar' => 'إدارة تقنية المعلومات',
                'name_en' => 'Information Technology',
                'organizational_level_id' => $levels['DIRECTOR'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "IT-SYS-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $it->id,
                'name_ar' => 'شعبة الأنظمة والشبكات',
                'name_en' => 'Systems & Networks',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "IT-SUPPORT-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $it->id,
                'name_ar' => 'شعبة الدعم الفني',
                'name_en' => 'Technical Support',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        // ═══════════════════════════════════════════════════════
        // إدارة المشتريات
        // ═══════════════════════════════════════════════════════
        $purchase = OrganizationUnit::updateOrCreate(
            ['code' => "PUR-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['DEPT'],
                'parent_id' => $gm->id,
                'name_ar' => 'إدارة المشتريات',
                'name_en' => 'Purchasing',
                'organizational_level_id' => $levels['DIRECTOR'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "PUR-PROC-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['SECTION'],
                'parent_id' => $purchase->id,
                'name_ar' => 'شعبة المشتريات والتوريد',
                'name_en' => 'Procurement',
                'organizational_level_id' => $levels['MANAGER'],
                'is_active' => true,
            ]
        );

        OrganizationUnit::updateOrCreate(
            ['code' => "PUR-VENDOR-{$companyId}", 'company_id' => $companyId],
            [
                'organization_unit_type_id' => $types['UNIT'],
                'parent_id' => $purchase->id,
                'name_ar' => 'وحدة إدارة الموردين',
                'name_en' => 'Vendor Management',
                'organizational_level_id' => $levels['SUPERVISOR'],
                'is_active' => true,
            ]
        );
    }
}
