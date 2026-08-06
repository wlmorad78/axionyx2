<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanySidebarSetting;
use Illuminate\Support\Facades\Config;

class Company1SidebarAllVisibleSeeder extends Seeder
{
    public function run(): void
    {
        $menuItems = Config::get('menu.items');
        $count = 0;

        foreach ($menuItems as $item) {
            CompanySidebarSetting::updateOrCreate(
                ['company_id' => 1, 'menu_key' => $item['key']],
                ['is_visible' => true]
            );
            $count++;

            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    CompanySidebarSetting::updateOrCreate(
                        ['company_id' => 1, 'menu_key' => $child['key']],
                        ['is_visible' => true]
                    );
                    $count++;
                }
            }
        }

        $this->command->info("{$count} menu items enabled for Company 1");
    }
}
