<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Collection;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerReturn;
use App\Models\CustomerReturnItem;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceDiscount;
use App\Models\SalesInvoiceTax;
use App\Models\SalesIncentive;
use App\Models\SalesIncentiveCondition;
use App\Models\SalesIncentiveReward;
use App\Models\SalesmanAssignment;
use App\Models\SalesmanSettlement;
use App\Models\SalesTarget;
use App\Models\SalesTargetDetail;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalesFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $branch = Branch::where('company_id', $company->id)->first();
            $customers = Customer::where('company_id', $company->id)->take(5)->get();
            $items = Item::where('company_id', $company->id)->take(5)->get();
            $adminUser = User::where('company_id', $company->id)->first();

            if ($customers->isEmpty() || $items->isEmpty()) continue;

            // Sales Incentives
            $incentive = SalesIncentive::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'INC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'name_ar' => 'تحفيز المبيعات الشهري',
                    'name_en' => 'Monthly Sales Incentive',
                    'description' => 'تحفيز المندوبين لتحقيق الأهداف الشهرية',
                    'valid_from' => '2026-01-01',
                    'valid_to' => '2026-12-31',
                    'is_active' => true,
                ]
            );

            SalesIncentiveCondition::create([
                'sales_incentive_id' => $incentive->id,
                'condition_type' => 'min_sales_amount',
                'condition_value' => 50000,
            ]);

            SalesIncentiveReward::create([
                'sales_incentive_id' => $incentive->id,
                'reward_type' => 'percentage',
                'reward_value' => 2,
            ]);

            // Sales Targets
            $salesReps = Employee::where('company_id', $company->id)->take(3)->get();

            $months = [
                ['month' => 1, 'year' => 2026],
                ['month' => 2, 'year' => 2026],
                ['month' => 3, 'year' => 2026],
            ];

            foreach ($months as $m) {
                foreach ($salesReps as $rep) {
                    $target = SalesTarget::updateOrCreate(
                        ['sales_rep_id' => $rep->id, 'year' => $m['year'], 'month' => $m['month']],
                        [
                            'target_amount' => 200000,
                            'target_customers' => 20,
                            'target_visits' => 40,
                        ]
                    );

                    foreach ($customers->take(3) as $customer) {
                        SalesTargetDetail::updateOrCreate(
                            ['sales_target_id' => $target->id, 'customer_id' => $customer->id],
                            ['target_amount' => 50000]
                        );
                    }
                }
            }

            // Sales Invoices
            for ($i = 0; $i < 5; $i++) {
                $subtotal = 10000 + ($i * 2000);
                $itemDiscount = 500 + ($i * 100);
                $invoiceDiscount = 200;
                $tax = 1400 + ($i * 280);
                $netTotal = $subtotal - $itemDiscount - $invoiceDiscount + $tax;

                $invoice = SalesInvoice::updateOrCreate(
                    ['invoice_no' => 'INV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'company_id' => $company->id,
                        'customer_id' => $customers[$i % $customers->count()]->id,
                        'branch_id' => $branch?->id,
                        'invoice_date' => now()->subDays($i * 3)->toDateString(),
                        'subtotal' => $subtotal,
                        'item_discount_total' => $itemDiscount,
                        'invoice_discount_total' => $invoiceDiscount,
                        'tax_total' => $tax,
                        'net_total' => $netTotal,
                        'paid_amount' => $i < 3 ? $netTotal : 5000,
                        'remaining_amount' => $i < 3 ? 0 : $netTotal - 5000,
                        'status' => $i < 3 ? 'paid' : 'partial',
                        'notes' => 'فاتورة مبيعات رقم ' . ($i + 1),
                    ]
                );

                // Invoice Items
                foreach ($items->take(2) as $j => $item) {
                    $qty = 10 + ($j * 5);
                    $price = 500 + ($j * 100);
                    $grossAmount = $qty * $price;
                    $discAmount = $grossAmount * 0.05;
                    $taxAmount = ($grossAmount - $discAmount) * 0.14;
                    $netAmount = $grossAmount - $discAmount + $taxAmount;

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'item_id' => $item->id,
                        'qty' => $qty,
                        'price' => $price,
                        'gross_amount' => $grossAmount,
                        'discount_type' => 'percentage',
                        'discount_value' => 5,
                        'discount_amount' => $discAmount,
                        'tax_percent' => 14,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                    ]);
                }

                // Invoice Discounts
                SalesInvoiceDiscount::create([
                    'sales_invoice_id' => $invoice->id,
                    'discount_type' => 'percentage',
                    'discount_value' => 5,
                    'discount_amount' => $invoice->item_discount_total,
                    'reason' => 'خصم عام',
                ]);

                // Invoice Taxes
                SalesInvoiceTax::create([
                    'sales_invoice_id' => $invoice->id,
                    'tax_name' => 'VAT',
                    'tax_percent' => 14,
                    'tax_amount' => $invoice->tax_total,
                ]);

                // Collections
                if ($i < 3) {
                    Collection::updateOrCreate(
                        ['company_id' => $company->id, 'collection_no' => 'COL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                        [
                            'customer_id' => $customers[$i % $customers->count()]->id,
                            'sales_invoice_id' => $invoice->id,
                            'collection_date' => now()->subDays($i * 3 + 5)->toDateString(),
                            'amount' => $invoice->net_total,
                            'payment_method_id' => null,
                            'status' => 'collected',
                        ]
                    );
                }
            }

            // Salesman Settlements
            $employee = \App\Models\Employee::where('company_id', $company->id)->first();
            if ($employee) {
                SalesmanSettlement::updateOrCreate(
                    ['company_id' => $company->id, 'settlement_no' => 'STL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'sales_rep_id' => $employee->id,
                        'settlement_date' => now()->toDateString(),
                        'total_collections_value' => 30000,
                        'total_sales_value' => 35000,
                        'cash_difference' => -5000,
                        'status' => 'pending',
                    ]
                );
            }

            // Customer Returns
            $return = CustomerReturn::updateOrCreate(
                ['company_id' => $company->id, 'return_no' => 'CRET-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'customer_id' => $customers[0]->id,
                    'return_date' => now()->subDays(10)->toDateString(),
                    'net_total' => 500,
                    'status' => 'approved',
                    'notes' => 'منتج تالف',
                ]
            );

            if ($items->isNotEmpty()) {
                CustomerReturnItem::create([
                    'customer_return_id' => $return->id,
                    'item_id' => $items[0]->id,
                    'qty' => 2,
                    'price' => 250,
                    'net_amount' => 500,
                    'notes' => 'منتج تالف',
                ]);
            }
        }
    }
}
