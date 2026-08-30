<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use Illuminate\Database\Seeder;

class OwnerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedOwnerAccounts($company);
        }
    }

    private function seedOwnerAccounts(Company $company): void
    {
        $equityGroup = AccountGroup::where('code', 'LIKE', 'AG-EQ-' . $company->id)->first();

        $ownerAccounts = [
            [
                'code' => '3002-' . $company->id,
                'group' => $equityGroup,
                'name_ar' => 'حساب جاري المالك',
                'name_en' => 'Owner Current Account',
                'nature' => 'credit',
            ],
            [
                'code' => '3003-' . $company->id,
                'group' => $equityGroup,
                'name_ar' => 'سحوبات المالك',
                'name_en' => 'Owner Drawings',
                'nature' => 'debit',
            ],
            [
                'code' => '3004-' . $company->id,
                'group' => $equityGroup,
                'name_ar' => 'أصول مملوكة للمالك',
                'name_en' => 'Owner Assets',
                'nature' => 'debit',
            ],
        ];

        foreach ($ownerAccounts as $a) {
            Account::updateOrCreate(
                ['company_id' => $company->id, 'account_code' => $a['code']],
                [
                    'account_group_id' => $a['group']?->id,
                    'account_name' => $a['name_ar'],
                    'is_leaf' => true,
                    'allow_transactions' => true,
                    'status' => 'active',
                ]
            );
        }
    }
}
