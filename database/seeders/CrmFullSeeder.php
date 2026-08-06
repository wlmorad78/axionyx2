<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $customers = Customer::where('company_id', $company->id)->take(5)->get();
            $adminUser = User::where('company_id', $company->id)->first();

            // Leads
            $leadsData = [
                ['lead_name' => 'عميل محتمل 1 - Lead 1', 'source' => 'website', 'status' => 'new'],
                ['lead_name' => 'عميل محتمل 2 - Lead 2', 'source' => 'referral', 'status' => 'contacted'],
                ['lead_name' => 'عميل محتمل 3 - Lead 3', 'source' => 'cold_call', 'status' => 'qualified'],
            ];

            $leadModels = [];
            foreach ($leadsData as $i => $l) {
                $leadModels[] = Lead::updateOrCreate(
                    ['lead_code' => 'LEAD-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'lead_name' => $l['lead_name'],
                        'source' => $l['source'],
                        'status' => $l['status'],
                        'mobile' => '010' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                        'email' => 'lead' . ($i + 1) . '@example.com',
                    ]
                );
            }

            // Lead Activities
            foreach ($leadModels as $i => $lead) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'activity_date' => now()->subDays($i * 2)->toDateString(),
                    'activity_type' => 'call',
                    'notes' => 'مكالمة هاتفية مع العميل المحتمل',
                    'created_by' => $adminUser?->id,
                ]);
            }

            // Opportunities
            $opportunitiesData = [
                ['opportunity_name' => 'صفقة تجارية 1 - Deal 1', 'stage' => 'prospecting', 'expected_value' => 50000],
                ['opportunity_name' => 'صفقة تجارية 2 - Deal 2', 'stage' => 'qualification', 'expected_value' => 75000],
                ['opportunity_name' => 'صفقة تجارية 3 - Deal 3', 'stage' => 'negotiation', 'expected_value' => 120000],
            ];

            foreach ($opportunitiesData as $i => $o) {
                $lead = $leadModels[$i % count($leadModels)] ?? null;
                Opportunity::updateOrCreate(
                    ['opportunity_name' => $o['opportunity_name']],
                    [
                        'lead_id' => $lead?->id,
                        'expected_value' => $o['expected_value'],
                        'expected_close_date' => now()->addDays(30)->toDateString(),
                        'stage' => $o['stage'],
                        'status' => 'open',
                    ]
                );
            }

            // Document Categories
            $docCategories = [
                ['code' => 'DC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name' => 'عقود - Contracts'],
                ['code' => 'DC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name' => 'فواتير - Invoices'],
                ['code' => 'DC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name' => 'تقارير - Reports'],
                ['code' => 'DC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-04', 'name' => 'مراسلات - Correspondence'],
            ];

            $dcModels = [];
            foreach ($docCategories as $dc) {
                $dcModels[] = DocumentCategory::updateOrCreate(
                    ['code' => $dc['code']],
                    ['name' => $dc['name']]
                );
            }

            // Documents
            Document::updateOrCreate(
                ['document_name' => 'عقد توريد - Supply Contract'],
                [
                    'document_category_id' => $dcModels[0]?->id,
                    'reference_type' => 'Customer',
                    'reference_id' => $customers->first()?->id,
                    'file_path' => 'documents/contract-001.pdf',
                    'uploaded_by' => $adminUser?->id,
                ]
            );
        }
    }
}
