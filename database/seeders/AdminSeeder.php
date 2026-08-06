<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Representative;
use App\Models\Role;
use App\Models\SalesTerritory;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => RoleNames::ADMIN],
            ['code' => 'admin']
        );

        $salesRole = Role::firstOrCreate(
            ['name' => RoleNames::SALES_REP],
            ['code' => 'sales_rep']
        );

        $warehouseRole = Role::firstOrCreate(
            ['name' => RoleNames::WAREHOUSE_KEEPER],
            ['code' => 'warehouse_keeper']
        );

        $accountantRole = Role::firstOrCreate(
            ['name' => RoleNames::ACCOUNTANT],
            ['code' => 'accountant']
        );

        $salesTerritory = SalesTerritory::where('code', 'ALX-N')->first();

        $company1 = Company::first();
        $company2 = Company::skip(1)->first();
        $company3 = Company::skip(2)->first();

        $users = [
            [
                'usercode' => User::FIRST_USERCODE,
                'name' => 'مدير النظام',
                'phone' => '01000000000',
                'role' => $adminRole,
                'companies' => [],
            ],
            [
                'usercode' => User::FIRST_USERCODE + 1,
                'name' => 'أمين المخزن',
                'phone' => '01000000001',
                'role' => $warehouseRole,
                'companies' => array_filter([$company1, $company2]),
            ],
            [
                'usercode' => User::FIRST_USERCODE + 2,
                'name' => 'مندوب مبيعات',
                'phone' => '01000000002',
                'role' => $salesRole,
                'representative' => true,
                'companies' => array_filter([$company1]),
            ],
            [
                'usercode' => User::FIRST_USERCODE + 3,
                'name' => 'المحاسب',
                'phone' => '01000000003',
                'role' => $accountantRole,
                'companies' => array_filter([$company1, $company2, $company3]),
            ],
        ];

        foreach ($users as $data) {
            $defaultCompanyId = null;
            if (!empty($data['companies'])) {
                $defaultCompanyId = $data['companies'][0]->id;
            }

            $user = User::updateOrCreate(
                ['usercode' => $data['usercode']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'phone' => $data['phone'],
                    'is_active' => true,
                    'company_id' => $defaultCompanyId,
                ]
            );

            $user->roles()->syncWithoutDetaching([$data['role']->id]);

            if (!empty($data['companies'])) {
                $user->companies()->syncWithoutDetaching(
                    array_map(fn($c) => $c->id, $data['companies'])
                );
            }

            if (! empty($data['representative']) && $salesTerritory) {
                Representative::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'sales_territory_id' => $salesTerritory->id,
                        'target_amount' => 100000,
                        'commission_rate' => 5,
                    ]
                );
            }
        }
    }
}
