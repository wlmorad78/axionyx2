<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryComponentType;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayrollFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        $earningType = SalaryComponentType::where('code', 'EARNING')->first();
        $deductionType = SalaryComponentType::where('code', 'DEDUCTION')->first();

        $componentData = [
            ['code' => 'BASIC', 'name_ar' => 'الراتب الأساسي', 'name_en' => 'Basic Salary', 'type' => 'EARNING', 'is_taxable' => true, 'affects_insurance' => true],
            ['code' => 'HOUSING', 'name_ar' => 'بدل سكن', 'name_en' => 'Housing Allowance', 'type' => 'EARNING', 'is_taxable' => true, 'affects_insurance' => false],
            ['code' => 'TRANSPORT', 'name_ar' => 'بدل مواصلات', 'name_en' => 'Transport Allowance', 'type' => 'EARNING', 'is_taxable' => false, 'affects_insurance' => false],
            ['code' => 'OTHER_ALLOW', 'name_ar' => 'بدلات أخرى', 'name_en' => 'Other Allowances', 'type' => 'EARNING', 'is_taxable' => true, 'affects_insurance' => false],
            ['code' => 'OVERTIME', 'name_ar' => 'عمل إضافي', 'name_en' => 'Overtime', 'type' => 'EARNING', 'is_taxable' => true, 'affects_insurance' => false],
            ['code' => 'INSURANCE', 'name_ar' => 'التأمينات الاجتماعية', 'name_en' => 'Social Insurance', 'type' => 'DEDUCTION', 'is_taxable' => false, 'affects_insurance' => false],
            ['code' => 'TAX', 'name_ar' => 'الضريبة', 'name_en' => 'Income Tax', 'type' => 'DEDUCTION', 'is_taxable' => false, 'affects_insurance' => false],
            ['code' => 'ABSENCE', 'name_ar' => 'خصم غياب', 'name_en' => 'Absence Deduction', 'type' => 'DEDUCTION', 'is_taxable' => false, 'affects_insurance' => false],
            ['code' => 'ADVANCE', 'name_ar' => 'خصم سلف', 'name_en' => 'Advance Deduction', 'type' => 'DEDUCTION', 'is_taxable' => false, 'affects_insurance' => false],
        ];

        foreach ($companies as $company) {
            $components = [];
            foreach ($componentData as $c) {
                $type = $c['type'] === 'EARNING' ? $earningType : $deductionType;
                $components[$c['code']] = SalaryComponent::updateOrCreate(
                    ['code' => $c['code'] . '-' . $company->id],
                    [
                        'company_id' => $company->id,
                        'salary_component_type_id' => $type?->id,
                        'name_ar' => $c['name_ar'],
                        'name_en' => $c['name_en'],
                        'is_taxable' => $c['is_taxable'],
                        'affects_insurance' => $c['affects_insurance'],
                        'is_active' => true,
                    ]
                );
            }

            // Employee Salary Structures
            $employees = Employee::where('company_id', $company->id)->get();
            foreach ($employees as $emp) {
                if (isset($components['BASIC'])) {
                    EmployeeSalaryStructure::updateOrCreate(
                        ['employee_id' => $emp->id, 'salary_component_id' => $components['BASIC']->id],
                        ['amount' => 8000, 'effective_from' => '2023-01-15', 'is_current' => true]
                    );
                }
                if (isset($components['HOUSING'])) {
                    EmployeeSalaryStructure::updateOrCreate(
                        ['employee_id' => $emp->id, 'salary_component_id' => $components['HOUSING']->id],
                        ['amount' => 2000, 'effective_from' => '2023-01-15', 'is_current' => true]
                    );
                }
                if (isset($components['TRANSPORT'])) {
                    EmployeeSalaryStructure::updateOrCreate(
                        ['employee_id' => $emp->id, 'salary_component_id' => $components['TRANSPORT']->id],
                        ['amount' => 1000, 'effective_from' => '2023-01-15', 'is_current' => true]
                    );
                }
            }

            // Payroll Periods
            $periods = [
                ['period_name' => 'يناير 2026', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'status' => 'closed'],
                ['period_name' => 'فبراير 2026', 'period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'status' => 'closed'],
                ['period_name' => 'مارس 2026', 'period_start' => '2026-03-01', 'period_end' => '2026-03-31', 'status' => 'closed'],
                ['period_name' => 'أبريل 2026', 'period_start' => '2026-04-01', 'period_end' => '2026-04-30', 'status' => 'closed'],
                ['period_name' => 'مايو 2026', 'period_start' => '2026-05-01', 'period_end' => '2026-05-31', 'status' => 'open'],
                ['period_name' => 'يونيو 2026', 'period_start' => '2026-06-01', 'period_end' => '2026-06-30', 'status' => 'open'],
            ];

            $adminUser = User::where('company_id', $company->id)->first();

            foreach ($periods as $p) {
                $period = PayrollPeriod::updateOrCreate(
                    ['company_id' => $company->id, 'period_name' => $p['period_name']],
                    array_merge($p, ['company_id' => $company->id])
                );

                // Payroll Run
                if ($p['status'] === 'closed') {
                    $run = PayrollRun::updateOrCreate(
                        ['company_id' => $company->id, 'payroll_period_id' => $period->id],
                        [
                            'run_date' => $p['period_end'],
                            'status' => 'approved',
                            'created_by' => $adminUser?->id,
                        ]
                    );

                    // Payroll Run Details
                    foreach ($employees as $emp) {
                        PayrollRunDetail::updateOrCreate(
                            ['payroll_run_id' => $run->id, 'employee_id' => $emp->id],
                            [
                                'gross_salary' => 11000,
                                'total_allowances' => 3000,
                                'total_deductions' => 1500,
                                'net_salary' => 12500,
                            ]
                        );
                    }
                }
            }
        }
    }
}
