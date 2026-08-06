<?php

namespace Database\Seeders;

use App\Models\SalesTerritoryType;
use Illuminate\Database\Seeder;

class SalesTerritoryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'DS', 'name_ar' => 'توزيع مباشر', 'name_en' => 'Direct Distribution', 'is_system' => true],
            ['code' => 'WS', 'name_ar' => 'جملة', 'name_en' => 'Wholesale', 'is_system' => true],
            ['code' => 'KA', 'name_ar' => 'كبار العملاء', 'name_en' => 'Key Accounts', 'is_system' => true],
            ['code' => 'MER', 'name_ar' => 'دعاية وعرض', 'name_en' => 'Merchandising', 'is_system' => true],
        ];

        foreach ($types as $type) {
            SalesTerritoryType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
