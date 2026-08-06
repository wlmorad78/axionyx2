<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPriceList;
use App\Models\Item;
use App\Models\PricingCalculation;
use App\Models\PricingCalculationDetail;
use App\Models\PricingException;
use App\Models\PricingMethod;
use App\Models\PricingRule;
use App\Models\PricingRuleCondition;
use App\Models\PricingRuleItem;
use App\Models\PriceApprovalRequest;
use App\Models\PriceApprovalStep;
use App\Models\PriceList;
use App\Models\PriceLevel;
use App\Models\PricingAuditLog;
use App\Models\QuantityPriceBreak;
use App\Models\ContractPrice;
use App\Models\CustomerAgreement;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class AdvancedPricingFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $items = Item::where('company_id', $company->id)->take(3)->get();
            $customers = Customer::where('company_id', $company->id)->take(2)->get();
            $priceList = PriceList::where('company_id', $company->id)->first();
            $priceLevel = PriceLevel::where('company_id', $company->id)->first();

            if ($items->isEmpty() || !$priceList) continue;

            // Customer Price Lists
            if ($customers->isNotEmpty() && $priceLevel) {
                CustomerPriceList::updateOrCreate(
                    ['customer_id' => $customers[0]->id, 'price_list_id' => $priceList->id],
                    ['effective_from' => '2026-01-01', 'effective_to' => '2026-12-31']
                );
            }

            // Pricing Rule Conditions
            $rule = PricingRule::where('rule_code', 'LIKE', 'PR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '%')->first();
            if ($rule) {
                PricingRuleCondition::updateOrCreate(
                    ['pricing_rule_id' => $rule->id, 'condition_type' => 'min_quantity'],
                    ['condition_value' => '50']
                );

                foreach ($items->take(2) as $item) {
                    PricingRuleItem::updateOrCreate(
                        ['pricing_rule_id' => $rule->id, 'item_id' => $item->id],
                        ['base_price' => 50, 'price' => 42, 'minimum_price' => 38]
                    );
                }
            }

            // Quantity Price Breaks
            $ruleItems = PricingRuleItem::where('pricing_rule_id', $rule?->id)->get();
            foreach ($ruleItems->take(3) as $ruleItem) {
                QuantityPriceBreak::updateOrCreate(
                    ['pricing_rule_item_id' => $ruleItem->id, 'from_qty' => 100],
                    ['to_qty' => 0, 'price' => 40, 'discount_percent' => 20]
                );
            }

            // Contract Prices
            if ($customers->isNotEmpty() && $items->isNotEmpty()) {
                $agreement = CustomerAgreement::where('company_id', $company->id)->first();
                if ($agreement) {
                    ContractPrice::updateOrCreate(
                        ['customer_agreement_id' => $agreement->id, 'item_id' => $items[0]->id],
                        [
                            'contract_price' => 38,
                            'minimum_qty' => 500,
                        ]
                    );
                }
            }

            // Pricing Calculations
            if ($customers->isNotEmpty() && $items->isNotEmpty()) {
                $calc = PricingCalculation::create([
                    'reference_type' => 'SalesInvoice',
                    'reference_id' => 1,
                    'customer_id' => $customers[0]->id,
                    'item_id' => $items[0]->id,
                    'base_price' => 50,
                    'final_price' => 42.5,
                    'discount_amount' => 500,
                    'discount_percent' => 10,
                    'pricing_rule_id' => $rule?->id,
                ]);

                PricingCalculationDetail::create([
                    'pricing_calculation_id' => $calc->id,
                    'calculation_step' => 1,
                    'description' => 'Base price calculation',
                    'amount' => 5000,
                ]);
            }

            // Price Approval Requests
            if ($customers->isNotEmpty() && $items->isNotEmpty()) {
                $approval = PriceApprovalRequest::updateOrCreate(
                    ['request_no' => 'PAR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'customer_id' => $customers[0]->id,
                        'item_id' => $items[0]->id,
                        'requested_price' => 35,
                        'current_price' => 50,
                        'status' => 'PENDING',
                    ]
                );

                PriceApprovalStep::create([
                    'price_approval_request_id' => $approval->id,
                    'step_no' => 1,
                    'status' => 'PENDING',
                ]);
            }

            // Pricing Exceptions
            if ($rule && $customers->isNotEmpty() && $items->isNotEmpty()) {
                PricingException::updateOrCreate(
                    ['pricing_rule_id' => $rule->id, 'customer_id' => $customers[0]->id, 'item_id' => $items[0]->id],
                    [
                        'exception_price' => 30,
                        'effective_from' => '2026-01-01',
                        'effective_to' => '2026-12-31',
                    ]
                );
            }

            // Pricing Audit Log
            if ($customers->isNotEmpty() && $items->isNotEmpty()) {
                PricingAuditLog::create([
                    'reference_type' => 'ItemPrice',
                    'reference_id' => $items[0]->id,
                    'customer_id' => $customers[0]->id,
                    'item_id' => $items[0]->id,
                    'rule_applied' => 'Volume Discount',
                    'old_price' => 50,
                    'new_price' => 45,
                ]);
            }
        }
    }
}
