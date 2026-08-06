<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerClass;
use App\Models\CustomerGroup;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemSubCategory;
use App\Models\ProductCompany;
use App\Models\Representative;
use App\Models\Role;
use App\Models\SalesTerritory;
use App\Models\SalesTerritoryType;
use App\Models\Unit;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = $this->seedUnits();
        $territoryTypes = $this->seedTerritoryTypes();

        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedRoles($company);
            $this->seedBranch($company);
            $this->seedUsers($company);
            $this->seedCustomerData($company);
            $this->seedItemData($company, $units);
            $this->seedTerritories($company, $territoryTypes);
            $this->seedCustomers($company);
            $this->seedItems($company, $units);
        }
    }

    private function seedUnits(): array
    {
        $unitsData = [
            ['code' => 'UNIT-PC', 'name_ar' => 'قطعة', 'name_en' => 'Piece', 'symbol' => 'PC'],
            ['code' => 'UNIT-KG', 'name_ar' => 'كيلو', 'name_en' => 'Kilogram', 'symbol' => 'KG'],
            ['code' => 'UNIT-LTR', 'name_ar' => 'لتر', 'name_en' => 'Liter', 'symbol' => 'L'],
            ['code' => 'UNIT-BOX', 'name_ar' => 'كرتون', 'name_en' => 'Box', 'symbol' => 'BOX'],
            ['code' => 'UNIT-CS', 'name_ar' => 'كرتنة', 'name_en' => 'Case', 'symbol' => 'CS'],
        ];

        $units = [];
        foreach ($unitsData as $data) {
            $units[] = Unit::updateOrCreate(['code' => $data['code']], $data);
        }
        return $units;
    }

    private function seedTerritoryTypes(): array
    {
        $types = [
            ['code' => 'TT-REGION', 'name_ar' => 'منطقة', 'name_en' => 'Region'],
            ['code' => 'TT-CITY', 'name_ar' => 'مدينة', 'name_en' => 'City'],
        ];

        $result = [];
        foreach ($types as $data) {
            $result[] = SalesTerritoryType::updateOrCreate(['code' => $data['code']], $data);
        }
        return $result;
    }

    private function seedRoles(Company $company): void
    {
        Role::firstOrCreate(
            ['name' => RoleNames::ADMIN, 'company_id' => $company->id],
            ['code' => 'admin_' . $company->id]
        );
        Role::firstOrCreate(
            ['name' => RoleNames::ACCOUNTANT, 'company_id' => $company->id],
            ['code' => 'accountant_' . $company->id]
        );
        Role::firstOrCreate(
            ['name' => RoleNames::WAREHOUSE_KEEPER, 'company_id' => $company->id],
            ['code' => 'warehouse_keeper_' . $company->id]
        );
        Role::firstOrCreate(
            ['name' => RoleNames::SALES_REP, 'company_id' => $company->id],
            ['code' => 'sales_rep_' . $company->id]
        );
        Role::firstOrCreate(
            ['name' => RoleNames::SALES_MANAGER, 'company_id' => $company->id],
            ['code' => 'sales_manager_' . $company->id]
        );
        Role::firstOrCreate(
            ['name' => RoleNames::SALES_SUPERVISOR, 'company_id' => $company->id],
            ['code' => 'sales_supervisor_' . $company->id]
        );
    }

    private function seedBranch(Company $company): void
    {
        Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'BR-' . str_pad($company->id, 5, '0', STR_PAD_LEFT)],
            [
                'name' => 'الفرع الرئيسي',
                'name_ar' => 'الفرع الرئيسي',
                'name_en' => 'Head Office',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );
    }

    private function seedUsers(Company $company): void
    {
        $adminRole = Role::where('name', RoleNames::ADMIN)->where('company_id', $company->id)->first();
        $accountantRole = Role::where('name', RoleNames::ACCOUNTANT)->where('company_id', $company->id)->first();
        $warehouseRole = Role::where('name', RoleNames::WAREHOUSE_KEEPER)->where('company_id', $company->id)->first();
        $salesRepRole = Role::where('name', RoleNames::SALES_REP)->where('company_id', $company->id)->first();
        $salesManRole = Role::where('name', RoleNames::SALES_MAN)->where('company_id', $company->id)->first();
        $salesManagerRole = Role::where('name', RoleNames::SALES_MANAGER)->where('company_id', $company->id)->first();
        $salesSupervisorRole = Role::where('name', RoleNames::SALES_SUPERVISOR)->where('company_id', $company->id)->first();

        $baseCode = ($company->id - 1) * 100;

        $admin = $this->createUser($baseCode + 1, 'مدير شركة ' . $company->id, $company->id, $adminRole);
        $this->createUser($baseCode + 2, 'محاسب شركة ' . $company->id, $company->id, $accountantRole);
        $this->createUser($baseCode + 3, 'امين مخزن شركة ' . $company->id, $company->id, $warehouseRole);
        $salesManager = $this->createUser($baseCode + 4, 'مدير مبيعات شركة ' . $company->id, $company->id, $salesManagerRole);
        $salesSupervisor = $this->createUser($baseCode + 5, 'مشرف مبيعات شركة ' . $company->id, $company->id, $salesSupervisorRole);
        $this->createUser($baseCode + 6, 'منفذ مبيعات شركة ' . $company->id, $company->id, $salesManRole);

        for ($i = 1; $i <= 10; $i++) {
            $this->createUser($baseCode + 5 + $i, "مندوب $i - شركة $company->id", $company->id, $salesRepRole);
        }
    }

    private function createUser(int $usercode, string $name, int $companyId, Role $role): User
    {
        $user = User::updateOrCreate(
            ['usercode' => $usercode],
            [
                'name' => $name,
                'password' => 'password',
                'phone' => '010' . str_pad($usercode, 8, '0', STR_PAD_LEFT),
                'is_active' => true,
                'company_id' => $companyId,
            ]
        );
        $user->roles()->syncWithoutDetaching([$role->id]);
        return $user;
    }

    private function seedCustomerData(Company $company): void
    {
        CustomerGroup::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'CG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
            ['name_ar' => 'عملاء عامين', 'name_en' => 'General', 'is_active' => true]
        );

        CustomerClass::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'CC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
            ['name_ar' => 'فئة عادية', 'name_en' => 'Regular', 'credit_limit' => 50000, 'is_active' => true]
        );

        ProductCompany::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'PC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
            ['name_ar' => 'الشركة المصنعة ' . $company->id, 'name_en' => 'Manufacturer ' . $company->id, 'is_active' => true]
        );

        $cat = ItemCategory::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
            ['name_ar' => 'منتجات عامة', 'name_en' => 'General Products', 'is_active' => true]
        );

        ItemSubCategory::firstOrCreate(
            ['item_category_id' => $cat->id, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
            ['name_ar' => '分类 فرعية', 'name_en' => 'Sub Category', 'is_active' => true]
        );
    }

    private function seedItemData(Company $company, array $units): void
    {
    }

    private function seedTerritories(Company $company, array $territoryTypes): void
    {
        $branch = Branch::where('company_id', $company->id)->first();
        $type = $territoryTypes[0] ?? SalesTerritoryType::first();

        SalesTerritory::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'ST-' . str_pad($company->id * 10 + 1, 5, '0', STR_PAD_LEFT)],
            [
                'branch_id' => $branch?->id,
                'sales_territory_type_id' => $type->id,
                'name_ar' => 'المنطقة الأولى - شركة ' . $company->id,
                'name_en' => 'Territory 1 - Company ' . $company->id,
                'is_active' => true,
            ]
        );

        SalesTerritory::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'ST-' . str_pad($company->id * 10 + 2, 5, '0', STR_PAD_LEFT)],
            [
                'branch_id' => $branch?->id,
                'sales_territory_type_id' => $type->id,
                'name_ar' => 'المنطقة الثانية - شركة ' . $company->id,
                'name_en' => 'Territory 2 - Company ' . $company->id,
                'is_active' => true,
            ]
        );
    }

    private function seedCustomers(Company $company): void
    {
        $group = CustomerGroup::where('company_id', $company->id)->first();
        $class = CustomerClass::where('company_id', $company->id)->first();

        $names = [
            ['ar' => 'عميل أول', 'en' => 'Customer 1'],
            ['ar' => 'عميل ثاني', 'en' => 'Customer 2'],
            ['ar' => 'عميل ثالث', 'en' => 'Customer 3'],
            ['ar' => 'عميل رابع', 'en' => 'Customer 4'],
            ['ar' => 'عميل خامس', 'en' => 'Customer 5'],
            ['ar' => 'عميل سادس', 'en' => 'Customer 6'],
            ['ar' => 'عميل سابع', 'en' => 'Customer 7'],
            ['ar' => 'عميل ثامن', 'en' => 'Customer 8'],
            ['ar' => 'عميل تاسع', 'en' => 'Customer 9'],
            ['ar' => 'عميل عاشر', 'en' => 'Customer 10'],
        ];

        foreach ($names as $i => $name) {
            Customer::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'CUST-' . str_pad($company->id * 100 + $i + 1, 6, '0', STR_PAD_LEFT)],
                [
                    'customer_group_id' => $group?->id,
                    'customer_class_id' => $class?->id,
                    'name_ar' => $name['ar'] . ' - شركة ' . $company->id,
                    'name_en' => $name['en'] . ' - Co' . $company->id,
                    'phone' => '010' . str_pad(1000000 + $company->id * 100 + $i, 8, '0', STR_PAD_LEFT),
                    'credit_limit' => 10000,
                    'payment_term_days' => 30,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedItems(Company $company, array $units): void
    {
        $category = ItemCategory::where('company_id', $company->id)->first();
        $productCompany = ProductCompany::where('company_id', $company->id)->first();
        $unit = $units[0] ?? Unit::first();

        $items = [
            ['ar' => 'منتج أول', 'en' => 'Product 1', 'price' => 100],
            ['ar' => 'منتج ثاني', 'en' => 'Product 2', 'price' => 200],
            ['ar' => 'منتج ثالث', 'en' => 'Product 3', 'price' => 150],
            ['ar' => 'منتج رابع', 'en' => 'Product 4', 'price' => 300],
            ['ar' => 'منتج خامس', 'en' => 'Product 5', 'price' => 250],
            ['ar' => 'منتج سادس', 'en' => 'Product 6', 'price' => 175],
            ['ar' => 'منتج سابع', 'en' => 'Product 7', 'price' => 125],
            ['ar' => 'منتج ثامن', 'en' => 'Product 8', 'price' => 400],
            ['ar' => 'منتج تاسع', 'en' => 'Product 9', 'price' => 350],
            ['ar' => 'منتج عاشر', 'en' => 'Product 10', 'price' => 500],
        ];

        foreach ($items as $i => $item) {
            Item::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'ITEM-' . str_pad($company->id * 100 + $i + 1, 6, '0', STR_PAD_LEFT)],
                [
                    'item_category_id' => $category?->id,
                    'product_company_id' => $productCompany?->id,
                    'name_ar' => $item['ar'] . ' - شركة ' . $company->id,
                    'name_en' => $item['en'] . ' - Co' . $company->id,
                    'base_unit_id' => $unit?->id,
                    'minimum_stock' => 10,
                    'maximum_stock' => 1000,
                    'reorder_quantity' => 100,
                    'is_active' => true,
                ]
            );
        }
    }
}
