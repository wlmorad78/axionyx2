<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'CAR', 'name' => 'سيارة', 'description' => 'سيارات ركوب', 'icon' => '🚗', 'sort_order' => 1, 'is_active' => true],
            ['code' => 'VAN', 'name' => 'فان', 'description' => 'نقل خفيف', 'icon' => '🚐', 'sort_order' => 2, 'is_active' => true],
            ['code' => 'PICKUP', 'name' => 'بيك أب', 'description' => 'نصف نقل', 'icon' => '🛻', 'sort_order' => 3, 'is_active' => true],
            ['code' => 'TRUCK', 'name' => 'شاحنة', 'description' => 'نقل ثقيل', 'icon' => '🚚', 'sort_order' => 4, 'is_active' => true],
            ['code' => 'TRAILER', 'name' => 'مقطورة', 'description' => 'Trailer', 'icon' => '🚛', 'sort_order' => 5, 'is_active' => true],
            ['code' => 'MOTOR', 'name' => 'دراجة', 'description' => 'Motorcycle', 'icon' => '🏍️', 'sort_order' => 6, 'is_active' => true],
            ['code' => 'FORKLIFT', 'name' => 'رافعة', 'description' => 'Forklift', 'icon' => '🏗️', 'sort_order' => 7, 'is_active' => true],
            ['code' => 'REFRIGERATOR', 'name' => 'مبردة', 'description' => 'Refrigerated Truck', 'icon' => '🧊', 'sort_order' => 8, 'is_active' => true],
            ['code' => 'TANKER', 'name' => 'صهريج', 'description' => 'Tanker', 'icon' => '🛢️', 'sort_order' => 9, 'is_active' => true],
        ];

        foreach ($types as $type) {
            DB::table('vehicle_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
