<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductStructureSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PCS', 'name_ar' => 'قطعة', 'name_en' => 'Piece', 'symbol' => 'pc'],
            ['code' => 'BOX', 'name_ar' => 'علبة', 'name_en' => 'Box', 'symbol' => 'box'],
            ['code' => 'CTN', 'name_ar' => 'كرتونة', 'name_en' => 'Carton', 'symbol' => 'ctn'],
            ['code' => 'PKT', 'name_ar' => 'باكت', 'name_en' => 'Packet', 'symbol' => 'pkt'],
            ['code' => 'KG', 'name_ar' => 'كيلو', 'name_en' => 'Kilogram', 'symbol' => 'kg'],
            ['code' => 'G', 'name_ar' => 'جرام', 'name_en' => 'Gram', 'symbol' => 'g'],
            ['code' => 'L', 'name_ar' => 'لتر', 'name_en' => 'Liter', 'symbol' => 'L'],
            ['code' => 'ML', 'name_ar' => 'ميليلتر', 'name_en' => 'Milliliter', 'symbol' => 'ml'],
            ['code' => 'M', 'name_ar' => 'متر', 'name_en' => 'Meter', 'symbol' => 'm'],
            ['code' => 'CASE', 'name_ar' => 'كيس', 'name_en' => 'Case', 'symbol' => 'case'],
        ];

        foreach ($units as $u) {
            Unit::updateOrCreate(['code' => $u['code']], $u);
        }
    }
}
