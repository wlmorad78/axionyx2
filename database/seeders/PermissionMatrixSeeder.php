<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions');
        $definitions = $config['definitions'] ?? [];

        foreach ($definitions as $code => $descriptionAr) {
            Permission::updateOrCreate(
                ['code' => $code],
                ['name' => $descriptionAr]
            );
        }

        $this->command->info("Seeded " . count($definitions) . " permissions from config.");
    }
}
