<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CostCenter;
use App\Models\CostCenterType;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Company::first()?->id ?? 1;
        $expenseTypeId = CostCenterType::where('code', 'EXPENSE')->first()?->id ?? 1;

        $sales = CostCenter::updateOrCreate(
            ['code' => 'CC-0001'],
            ['company_id' => $companyId, 'cost_center_type_id' => $expenseTypeId, 'name_ar' => 'المبيعات', 'name_en' => 'Sales', 'is_active' => true]
        );

        CostCenter::updateOrCreate(
            ['code' => 'CC-0002'],
            ['company_id' => $companyId, 'cost_center_type_id' => $expenseTypeId, 'parent_id' => $sales->id, 'name_ar' => 'الإسكندرية', 'name_en' => 'Alexandria', 'is_active' => true]
        );
        CostCenter::updateOrCreate(
            ['code' => 'CC-0003'],
            ['company_id' => $companyId, 'cost_center_type_id' => $expenseTypeId, 'parent_id' => $sales->id, 'name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'is_active' => true]
        );
        CostCenter::updateOrCreate(
            ['code' => 'CC-0004'],
            ['company_id' => $companyId, 'cost_center_type_id' => $expenseTypeId, 'parent_id' => $sales->id, 'name_ar' => 'الدلتا', 'name_en' => 'Delta', 'is_active' => true]
        );
    }
}
