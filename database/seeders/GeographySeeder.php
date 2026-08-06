<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Governorate;
use App\Models\City;
use App\Models\District;
use App\Models\Street;
use App\Models\SalesTerritoryType;
use Illuminate\Database\Seeder;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        $egypt = Country::firstOrCreate(
            ['code' => 'EG'],
            ['iso2' => 'EG', 'name' => 'مصر', 'name_en' => 'Egypt', 'phone_code' => '+20', 'is_active' => true]
        );

        $alexandria = Governorate::firstOrCreate(
            ['name' => 'الإسكندرية'],
            ['country_id' => $egypt->id, 'code' => 'ALX', 'is_active' => true]
        );

        $giza = Governorate::firstOrCreate(
            ['name' => 'الجيزة'],
            ['country_id' => $egypt->id, 'code' => 'GIZ', 'is_active' => true]
        );

        $alexCity = City::firstOrCreate(
            ['governorate_id' => $alexandria->id, 'name' => 'الإسكندرية'],
            ['is_active' => true]
        );

        $gizaCity = City::firstOrCreate(
            ['governorate_id' => $giza->id, 'name' => 'الجيزة'],
            ['is_active' => true]
        );

        $districts = [
            [$alexCity->id, 'حي الجمارك'],
            [$alexCity->id, 'حي المنتزه'],
            [$alexCity->id, 'حي العجمي'],
            [$gizaCity->id, 'حي الدقي'],
            [$gizaCity->id, 'حي الهرم'],
        ];

        $createdDistricts = [];
        foreach ($districts as [$cityId, $name]) {
            $createdDistricts[] = District::firstOrCreate(
                ['city_id' => $cityId, 'name' => $name],
                ['is_active' => true]
            );
        }

        $streets = [
            [$createdDistricts[0]->id, 'شارع المينا الرئيسي'],
            [$createdDistricts[0]->id, 'شارع الكورنيش'],
            [$createdDistricts[1]->id, 'شارع فوزي معاذ'],
            [$createdDistricts[2]->id, 'شارع مصطفى كامل'],
        ];

        foreach ($streets as [$districtId, $name]) {
            Street::firstOrCreate(
                ['district_id' => $districtId, 'name' => $name],
                ['is_active' => true]
            );
        }

        SalesTerritoryType::firstOrCreate(
            ['code' => 'GEO'],
            ['name_ar' => 'جغرافي', 'name_en' => 'Geographic', 'is_active' => true]
        );
    }
}
