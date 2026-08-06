<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CustomerTaxProfile;
use App\Models\ItemTaxProfile;
use App\Models\SupplierTaxProfile;
use App\Models\TaxCalculation;
use App\Models\TaxCalculationDetail;
use App\Models\TaxExemption;
use App\Models\TaxGroup;
use App\Models\TaxGroupDetail;
use App\Models\TaxJurisdiction;
use App\Models\TaxPeriod;
use App\Models\TaxRate;
use App\Models\TaxReturn;
use App\Models\TaxReturnDetail;
use App\Models\TaxRule;
use App\Models\TaxType;
use App\Models\WithholdingTaxCertificate;
use Illuminate\Database\Seeder;

class TaxFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        // Tax Types (per company)
        $vatTypes = [];
        $incomeTaxTypes = [];
        $withholdingTypes = [];
        foreach ($companies as $company) {
            $prefix = str_pad($company->id, 3, '0', STR_PAD_LEFT);
            $vatTypes[$company->id] = TaxType::updateOrCreate(['company_id' => $company->id, 'tax_code' => 'VAT-' . $prefix], ['tax_name' => 'ضريبة القيمة المضافة', 'tax_category' => 'VAT', 'is_active' => true]);
            $incomeTaxTypes[$company->id] = TaxType::updateOrCreate(['company_id' => $company->id, 'tax_code' => 'INCOME_TAX-' . $prefix], ['tax_name' => 'ضريبة الدخل', 'tax_category' => 'OTHER', 'is_active' => true]);
            $withholdingTypes[$company->id] = TaxType::updateOrCreate(['company_id' => $company->id, 'tax_code' => 'WITHHOLDING-' . $prefix], ['tax_name' => 'ضريبة الاقتطاع', 'tax_category' => 'WITHHOLDING', 'is_active' => true]);
        }

        // Tax Rates
        foreach ($companies as $company) {
            $vatType = $vatTypes[$company->id];
            $incomeTaxType = $incomeTaxTypes[$company->id];
            $withholdingType = $withholdingTypes[$company->id];
            TaxRate::updateOrCreate(['tax_type_id' => $vatType->id, 'rate_percent' => 14], ['effective_from' => '2026-01-01', 'is_default' => true]);
            TaxRate::updateOrCreate(['tax_type_id' => $vatType->id, 'rate_percent' => 0], ['effective_from' => '2026-01-01', 'is_default' => false]);
            TaxRate::updateOrCreate(['tax_type_id' => $incomeTaxType->id, 'rate_percent' => 10], ['effective_from' => '2026-01-01', 'is_default' => true]);
            TaxRate::updateOrCreate(['tax_type_id' => $withholdingType->id, 'rate_percent' => 5], ['effective_from' => '2026-01-01', 'is_default' => true]);
        }

        foreach ($companies as $company) {
            // Tax Groups
            $taxGroup = TaxGroup::updateOrCreate(
                ['company_id' => $company->id, 'group_code' => 'TG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                ['group_name' => 'مجموعة ضريبة القيمة المضافة']
            );

            TaxGroupDetail::create([
                'tax_group_id' => $taxGroup->id,
                'tax_type_id' => $vatTypes[$company->id]->id,
                'calculation_order' => 1,
            ]);

            // Tax Jurisdictions
            TaxJurisdiction::updateOrCreate(
                ['jurisdiction_code' => 'EG-' . $company->id],
                ['country_id' => 1, 'jurisdiction_name' => 'مصر']
            );

            // Tax Periods
            $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'];
            foreach ($months as $i => $month) {
                TaxPeriod::updateOrCreate(
                    ['company_id' => $company->id, 'period_name' => $month . ' 2026'],
                    [
                        'start_date' => date('Y') . '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '-01',
                        'end_date' => date('Y') . '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime(date('Y') . '-' . ($i + 1) . '-01')),
                        'status' => $i < 4 ? 'CLOSED' : 'OPEN',
                    ]
                );
            }

            // Tax Exemptions
            TaxExemption::updateOrCreate(
                ['company_id' => $company->id, 'exemption_code' => 'EX-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                ['exemption_name' => 'إعفاء طبي', 'description' => 'إعفاء المنتجات الطبية', 'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31']
            );

            // Tax Rules
            TaxRule::updateOrCreate(
                ['company_id' => $company->id, 'rule_name' => 'قاعدة القيمة المضافة'],
                ['tax_group_id' => $taxGroup->id, 'priority' => 1, 'effective_from' => '2026-01-01']
            );

            // Customer Tax Profiles
            $customers = \App\Models\Customer::where('company_id', $company->id)->take(2)->get();
            foreach ($customers as $customer) {
                CustomerTaxProfile::updateOrCreate(
                    ['customer_id' => $customer->id],
                    ['tax_group_id' => $taxGroup->id, 'tax_registration_no' => 'TAX-' . $customer->id, 'is_taxable' => true]
                );
            }

            // Supplier Tax Profiles
            $suppliers = \App\Models\Supplier::where('company_id', $company->id)->take(2)->get();
            foreach ($suppliers as $supplier) {
                SupplierTaxProfile::updateOrCreate(
                    ['supplier_id' => $supplier->id],
                    ['tax_group_id' => $taxGroup->id, 'tax_registration_no' => 'STAX-' . $supplier->id, 'is_taxable' => true]
                );
            }

            // Item Tax Profiles
            $items = \App\Models\Item::where('company_id', $company->id)->take(3)->get();
            foreach ($items as $item) {
                ItemTaxProfile::updateOrCreate(
                    ['item_id' => $item->id],
                    ['tax_group_id' => $taxGroup->id, 'is_taxable' => true]
                );
            }

            // Tax Calculations
            $calc = TaxCalculation::create([
                'reference_type' => 'SalesInvoice',
                'reference_id' => 1,
                'calculation_date' => now()->toDateString(),
                'taxable_amount' => 10000,
                'tax_amount' => 1400,
                'total_amount' => 11400,
            ]);

            TaxCalculationDetail::create([
                'tax_calculation_id' => $calc->id,
                'tax_type_id' => $vatTypes[$company->id]->id,
                'tax_rate' => 14,
                'taxable_amount' => 10000,
                'tax_amount' => 1400,
                'calculation_order' => 1,
            ]);

            // Tax Returns
            $period = TaxPeriod::where('company_id', $company->id)->first();
            if ($period) {
                $return = TaxReturn::updateOrCreate(
                    ['company_id' => $company->id, 'return_no' => 'TRET-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    ['tax_period_id' => $period->id, 'submission_date' => now()->toDateString(), 'total_sales' => 100000, 'total_purchases' => 60000, 'output_tax' => 14000, 'input_tax' => 8400, 'net_tax' => 5600, 'status' => 'SUBMITTED']
                );

                TaxReturnDetail::create([
                    'tax_return_id' => $return->id,
                'tax_type_id' => $vatTypes[$company->id]->id,
                    'taxable_amount' => 100000,
                    'tax_amount' => 14000,
                ]);
            }

            // Withholding Tax Certificates
            if ($customers->isNotEmpty()) {
                WithholdingTaxCertificate::updateOrCreate(
                    ['certificate_no' => 'WTC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    ['customer_id' => $customers[0]->id, 'tax_type_id' => $withholdingTypes[$company->id]->id, 'certificate_date' => now()->toDateString(), 'amount' => 5000, 'tax_amount' => 250]
                );
            }
        }
    }
}
