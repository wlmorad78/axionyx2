<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\PriceLevel;
use App\Models\Customer;
use App\Models\CustomerPriceLevel;
use App\Models\CustomerSpecialPrice;
use App\Models\PricingMethod;
use App\Models\PricingRule;
use App\Models\PricingRuleCondition;
use App\Models\PricingRuleItem;
use App\Models\QuantityPriceBreak;
use App\Models\ContractPrice;
use App\Models\CustomerAgreement;
use App\Models\CustomerAgreementType;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class PricingFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $items = Item::where('company_id', $company->id)->take(5)->get();
            $customers = Customer::where('company_id', $company->id)->take(3)->get();
            $priceList = PriceList::where('company_id', $company->id)->first();
            $unit = Unit::where('code', 'PCS')->first();

            if ($items->isEmpty() || !$priceList) continue;

            // Pricing Methods
            $prefix = str_pad($company->id, 3, '0', STR_PAD_LEFT);
            PricingMethod::updateOrCreate(
                ['company_id' => $company->id, 'method_code' => 'FIXED-' . $prefix],
                ['method_name' => 'سعر ثابت', 'is_active' => true]
            );

            PricingMethod::updateOrCreate(
                ['company_id' => $company->id, 'method_code' => 'COST_PLUS-' . $prefix],
                ['method_name' => 'التكلفة زائد', 'is_active' => true]
            );

            // Price Levels
            $levels = [
                ['level_code' => 'RETAIL-' . $company->id, 'level_name' => 'سعر التجزئة', 'priority' => 1],
                ['level_code' => 'WHOLESALE-' . $company->id, 'level_name' => 'سعر الجملة', 'priority' => 2],
                ['level_code' => 'VIP-' . $company->id, 'level_name' => 'سعر VIP', 'priority' => 3],
            ];

            foreach ($levels as $l) {
                PriceLevel::updateOrCreate(
                    ['level_code' => $l['level_code'], 'company_id' => $company->id],
                    ['level_name' => $l['level_name'], 'priority' => $l['priority'], 'is_active' => true]
                );
            }

            // Customer Price Levels
            foreach ($customers as $i => $customer) {
                $level = PriceLevel::where('level_code', 'LIKE', '%' . $company->id)->first();
                if ($level) {
                    CustomerPriceLevel::updateOrCreate(
                        ['customer_id' => $customer->id, 'price_level_id' => $level->id],
                        ['start_date' => '2026-01-01']
                    );
                }
            }

            // Customer Special Prices
            if ($items->isNotEmpty() && $customers->isNotEmpty()) {
                CustomerSpecialPrice::updateOrCreate(
                    ['customer_id' => $customers[0]->id, 'item_id' => $items[0]->id],
                    [
                        'price' => 45,
                        'start_date' => '2026-01-01',
                        'end_date' => '2026-12-31',
                    ]
                );
            }

            // Pricing Rules
            $rule = PricingRule::updateOrCreate(
                ['rule_code' => 'PR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'rule_name' => 'خصم الكمية الكبيرة',
                    'rule_type' => 'PRICE_LEVEL',
                    'priority' => 1,
                    'start_date' => '2026-01-01',
                    'is_active' => true,
                ]
            );

            PricingRuleCondition::create([
                'pricing_rule_id' => $rule->id,
                'condition_type' => 'min_quantity',
                'condition_value' => '100',
            ]);

            foreach ($items->take(2) as $item) {
                PricingRuleItem::create([
                    'pricing_rule_id' => $rule->id,
                    'item_id' => $item->id,
                    'base_price' => 50,
                    'price' => 45,
                    'minimum_price' => 40,
                ]);
            }

            // Quantity Price Breaks
            $ruleItems = PricingRuleItem::where('pricing_rule_id', $rule->id)->get();
            foreach ($ruleItems->take(3) as $ruleItem) {
                QuantityPriceBreak::updateOrCreate(
                    ['pricing_rule_item_id' => $ruleItem->id, 'from_qty' => 50],
                    ['to_qty' => 99, 'price' => 48, 'discount_percent' => 4]
                );

                QuantityPriceBreak::updateOrCreate(
                    ['pricing_rule_item_id' => $ruleItem->id, 'from_qty' => 100],
                    ['to_qty' => 0, 'price' => 45, 'discount_percent' => 10]
                );
            }

            // Contract Prices
            if ($customers->isNotEmpty() && $items->isNotEmpty()) {
                $agreementType = CustomerAgreementType::first();
                $agreement = CustomerAgreement::updateOrCreate(
                    ['company_id' => $company->id, 'customer_id' => $customers[0]->id, 'agreement_no' => 'CA-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'agreement_type_id' => $agreementType?->id,
                        'start_date' => now()->toDateString(),
                        'end_date' => now()->addYear()->toDateString(),
                        'status' => 'ACTIVE',
                    ]
                );

                ContractPrice::updateOrCreate(
                    ['customer_agreement_id' => $agreement->id, 'item_id' => $items[0]->id],
                    [
                        'contract_price' => 42,
                        'minimum_qty' => 200,
                    ]
                );
            }
        }
    }
}
