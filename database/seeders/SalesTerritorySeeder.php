<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SalesTerritory;
use App\Models\SalesTerritoryType;
use Illuminate\Database\Seeder;

class SalesTerritorySeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Company::first()?->id ?? 1;

        $dsType = SalesTerritoryType::where('code', 'DS')->first()?->id ?? 1;
        $wsType = SalesTerritoryType::where('code', 'WS')->first()?->id ?? 1;
        $kaType = SalesTerritoryType::where('code', 'KA')->first()?->id ?? 1;
        $merType = SalesTerritoryType::where('code', 'MER')->first()?->id ?? 1;

        $ds = SalesTerritory::updateOrCreate(
            ['code' => 'ST-0001'],
            ['company_id' => $companyId, 'sales_territory_type_id' => $dsType, 'name_ar' => 'DS الإسكندرية', 'name_en' => 'DS Alex', 'is_active' => true]
        );

        SalesTerritory::updateOrCreate(
            ['code' => 'ST-0002'],
            ['company_id' => $companyId, 'sales_territory_type_id' => $dsType, 'parent_id' => $ds->id, 'name_ar' => 'DS شرق الإسكندرية', 'name_en' => 'DS East Alex', 'is_active' => true]
        );
        SalesTerritory::updateOrCreate(
            ['code' => 'ST-0003'],
            ['company_id' => $companyId, 'sales_territory_type_id' => $dsType, 'parent_id' => $ds->id, 'name_ar' => 'DS غرب الإسكندرية', 'name_en' => 'DS West Alex', 'is_active' => true]
        );

        SalesTerritory::updateOrCreate(
            ['code' => 'ST-0004'],
            ['company_id' => $companyId, 'sales_territory_type_id' => $wsType, 'name_ar' => 'جملة الإسكندرية', 'name_en' => 'WS Alex', 'is_active' => true]
        );

        SalesTerritory::updateOrCreate(
            ['code' => 'ST-0005'],
            ['company_id' => $companyId, 'sales_territory_type_id' => $kaType, 'name_ar' => 'كبار العملاء الإسكندرية', 'name_en' => 'Key Account Alex', 'is_active' => true]
        );

        SalesTerritory::updateOrCreate(
            ['code' => 'ST-0006'],
            ['company_id' => $companyId, 'sales_territory_type_id' => $merType, 'name_ar' => 'دعاية وعرض الإسكندرية', 'name_en' => 'Merchandising Alex', 'is_active' => true]
        );
    }
}
