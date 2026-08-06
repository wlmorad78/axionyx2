<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Foundation
            FoundationSeeder::class,

            // 2. Companies
            CompanySeeder::class,

            // 3. Reference Data (from existing seeders)
            GeographySeeder::class,
            ReferenceDataSeeder::class,
            CompanyScopedReferenceSeeder::class,
            WarehouseTypeSeeder::class,
            TreasuryTypeSeeder::class,
            CostCenterTypeSeeder::class,
            SalesTerritoryTypeSeeder::class,
            AttendanceSeeder::class,
            EmployeeContractSeeder::class,
            PayrollSeeder::class,
            AdminNavigationSeeder::class,
            SubscriptionPlanSeeder::class,
            PlanDemoUserSeeder::class,
            MerchandisingModuleSeeder::class,
            DepartmentSeeder::class,
            PositionLevelSeeder::class,
            JobPositionSeeder::class,

            // 4. New Comprehensive Seeders
            WarehouseTreasurySeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            HrFullSeeder::class,
            UserEmployeeSeeder::class,
            PayrollFullSeeder::class,
            CustomerFullSeeder::class,
            ItemFullSeeder::class,
            DistributionSeeder::class,
            SalesFullSeeder::class,
            PurchaseFullSeeder::class,
            AccountingFullSeeder::class,
            InventoryFullSeeder::class,
            PricingFullSeeder::class,
            AdvancedPricingFullSeeder::class,
            TaxFullSeeder::class,
            FleetFullSeeder::class,
            CrmFullSeeder::class,
            WorkflowFullSeeder::class,
            NotificationFullSeeder::class,
            MerchandisingFullSeeder::class,
            SurveyFullSeeder::class,
            CompetitorFullSeeder::class,
            VehicleInventoryFullSeeder::class,
            TreasuryOperationFullSeeder::class,
            IntegrationFullSeeder::class,
            DefaultAccountsSeeder::class,
            HandheldUserSeeder::class,
        ]);
    }
}
