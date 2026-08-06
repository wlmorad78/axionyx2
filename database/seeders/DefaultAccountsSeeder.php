<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\AccountGroup;

class DefaultAccountsSeeder extends Seeder
{
    /**
     * Seed the default accounts used for auto journal entries.
     *
     * These accounts are used when creating journal entries from
     * sales invoices and purchase invoices.
     */
    public function run(): void
    {
        // Ensure account types exist
        $assetType = AccountType::firstOrCreate(['code' => 'asset'], ['name' => 'الأصول', 'nature' => 'debit']);
        $liabilityType = AccountType::firstOrCreate(['code' => 'liability'], ['name' => 'الخصوم', 'nature' => 'credit']);
        $revenueType = AccountType::firstOrCreate(['code' => 'revenue'], ['name' => 'الإيرادات', 'nature' => 'credit']);
        $expenseType = AccountType::firstOrCreate(['code' => 'expense'], ['name' => 'المصروفات', 'nature' => 'debit']);

        // Ensure account groups exist
        $arGroup = AccountGroup::firstOrCreate(
            ['code' => '110', 'company_id' => null],
            ['name' => 'العملاء', 'account_type_id' => $assetType->id, 'description' => 'حسابات العملاء']
        );
        $apGroup = AccountGroup::firstOrCreate(
            ['code' => '210', 'company_id' => null],
            ['name' => 'الموردون', 'account_type_id' => $liabilityType->id, 'description' => 'حسابات الموردين']
        );
        $taxGroup = AccountGroup::firstOrCreate(
            ['code' => '220', 'company_id' => null],
            ['name' => 'الضرائب', 'account_type_id' => $liabilityType->id, 'description' => 'حسابات الضرائب']
        );
        $salesGroup = AccountGroup::firstOrCreate(
            ['code' => '600', 'company_id' => null],
            ['name' => 'إيرادات المبيعات', 'account_type_id' => $revenueType->id, 'description' => 'إيرادات المبيعات']
        );
        $purchaseGroup = AccountGroup::firstOrCreate(
            ['code' => '500', 'company_id' => null],
            ['name' => 'المشتريات', 'account_type_id' => $expenseType->id, 'description' => 'مشتريات']
        );

        $defaultAccounts = [
            // Assets
            ['code' => '1101', 'name' => 'العملاء',         'group' => $arGroup,     'type' => $assetType],
            ['code' => '1102', 'name' => 'ضريبة القيمة المضافة (مدفوعة مقدماً)', 'group' => $arGroup, 'type' => $assetType],

            // Liabilities
            ['code' => '2101', 'name' => 'الموردون',        'group' => $apGroup,     'type' => $liabilityType],
            ['code' => '2201', 'name' => 'ضريبة القيمة المضافة', 'group' => $taxGroup, 'type' => $liabilityType],

            // Revenue
            ['code' => '6001', 'name' => 'إيرادات المبيعات', 'group' => $salesGroup,  'type' => $revenueType],

            // Expenses
            ['code' => '5001', 'name' => 'المشتريات',       'group' => $purchaseGroup, 'type' => $expenseType],
        ];

        foreach ($defaultAccounts as $acc) {
            Account::firstOrCreate(
                ['account_code' => $acc['code'], 'company_id' => null],
                [
                    'account_name'      => $acc['name'],
                    'account_type_id'   => $acc['type']->id,
                    'account_group_id'  => $acc['group']->id,
                    'is_leaf'           => true,
                    'allow_transactions'=> true,
                    'normal_balance'    => $acc['type']->nature,
                    'status'            => 'active',
                ]
            );
        }

        $this->command->info('Default accounts seeded successfully.');
    }
}
