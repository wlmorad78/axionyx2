<?php

namespace Database\Seeders;

use App\Models\WarehouseType;
use Illuminate\Database\Seeder;

class WarehouseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'MAIN',            'name_ar' => 'المخزن الرئيسي',            'name_en' => 'Main',                'is_active' => true],
            ['code' => 'RETURN',          'name_ar' => 'مخزن المرتجعات',            'name_en' => 'Return',              'is_active' => true],
            ['code' => 'DAMAGED',         'name_ar' => 'مخزن التاليف',             'name_en' => 'Damaged',             'is_active' => true],
            ['code' => 'TRANSIT',         'name_ar' => 'مخزن العبور والمنقول',         'name_en' => 'Transit',             'is_active' => true],
            ['code' => 'VEHICLE',         'name_ar' => 'مخزن السيارات',            'name_en' => 'Vehicle',             'is_active' => true],
            ['code' => 'RAW',             'name_ar' => 'مواد خام',              'name_en' => 'Raw Materials',       'is_active' => true],
            ['code' => 'WIP',             'name_ar' => 'المخزن قيد التصنيع والانتاج',    'name_en' => 'Work in Progress',    'is_active' => true],
            ['code' => 'FINISHED',        'name_ar' => 'المنتجات الجاهزة المصنعة',      'name_en' => 'Finished Goods',      'is_active' => true],
            ['code' => 'SCRAP',           'name_ar' => 'مخزن الخردة',             'name_en' => 'Scrap',               'is_active' => true],
            ['code' => 'QUARANTINE',      'name_ar' => 'مخزن الحجر الصحي',          'name_en' => 'Quarantine',          'is_active' => true],
            ['code' => 'RESERVE',         'name_ar' => 'مخزن الاحتياطي',           'name_en' => 'Reserve',             'is_active' => true],
            ['code' => 'DISPATCH',        'name_ar' => 'الشحن',                 'name_en' => 'Dispatch',            'is_active' => true],
            ['code' => 'CUSTOMS',         'name_ar' => 'الجمارك',               'name_en' => 'Customs Bonded',      'is_active' => true],
            ['code' => 'BULK',            'name_ar' => 'مخزن التجميع الكبير',         'name_en' => 'Bulk',                'is_active' => true],
            ['code' => 'COLD',            'name_ar' => 'التخزين البارد',            'name_en' => 'Cold Storage',        'is_active' => true],
            ['code' => 'HAZMAT',          'name_ar' => 'المواد الخطرة الخطيرة',       'name_en' => 'Hazardous',           'is_active' => true],
            ['code' => 'VIRTUAL',         'name_ar' => 'المخزن الافتراضي',          'name_en' => 'Virtual',             'is_active' => true],
            ['code' => 'PRODUCTION',      'name_ar' => 'الانتاج',               'name_en' => 'Production',          'is_active' => true],
            ['code' => 'OUTSOURCE',       'name_ar' => 'مخزن التصنيع الخارجي',       'name_en' => 'Outsourced',          'is_active' => true],
            ['code' => 'QUALITY',         'name_ar' => 'جودة',                 'name_en' => 'Quality Control',     'is_active' => true],
            ['code' => 'SEASONAL',        'name_ar' => 'المخزن الموسمي',            'name_en' => 'Seasonal',            'is_active' => true],
        ];

        foreach ($types as $type) {
            WarehouseType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
