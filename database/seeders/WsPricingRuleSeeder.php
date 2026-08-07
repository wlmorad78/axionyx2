<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Pricing\PricingRule;
use App\Models\Pricing\PricingRuleCondition;
use Illuminate\Database\Seeder;

class WsPricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $prefix = str_pad($company->id, 3, '0', STR_PAD_LEFT);

            $rule = PricingRule::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'rule_code' => 'WS-COST-' . $prefix,
                ],
                [
                    'rule_name' => 'عملاء الجملة - سعر الشراء',
                    'rule_type' => 'CUSTOMER_PRICE',
                    'priority' => 100,
                    'start_date' => '2026-01-01',
                    'is_active' => true,
                    'status' => 'active',
                ]
            );

            PricingRuleCondition::updateOrCreate(
                [
                    'pricing_rule_id' => $rule->id,
                    'condition_type' => 'customer_type',
                ],
                [
                    'condition_value' => 'WS',
                ]
            );
        }

        $this->command->info('WS Pricing Rule created for all companies.');
    }
}
