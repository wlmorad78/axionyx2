<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MarketingAsset;
use App\Models\MarketingAssetCategory;
use App\Models\MarketingAssetMaintenance;
use App\Models\MarketingAssetMovement;
use App\Models\MarketingCampaign;
use App\Models\MarketingCampaignCustomer;
use App\Models\MarketingMaterial;
use App\Models\MarketingSupportType;
use App\Models\CustomerAgreement;
use App\Models\CustomerAgreementHistory;
use App\Models\CustomerAgreementItem;
use App\Models\CustomerAgreementPayment;
use App\Models\CustomerAgreementTarget;
use App\Models\CustomerAgreementType;
use App\Models\CustomerMarketingAsset;
use App\Models\CustomerMarketingMaterial;
use App\Models\CustomerMarketingSupport;
use App\Models\CustomerRebateRule;
use App\Models\MerchandisingAudit;
use App\Models\MerchandisingAuditDetail;
use App\Models\MerchandisingAuditPhoto;
use App\Models\MerchandisingStandard;
use App\Models\MerchandisingStandardItem;
use App\Models\MerchandisingTask;
use App\Models\MerchandisingTaskAssignment;
use App\Models\MerchandisingVisit;
use App\Models\MerchandisingVisitDetail;
use App\Models\MerchandisingChecklist;
use App\Models\DisplayLocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class MerchandisingFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $customers = Customer::where('company_id', $company->id)->take(3)->get();
            $items = Item::where('company_id', $company->id)->take(3)->get();
            $adminUser = User::where('company_id', $company->id)->first();

            // Merchandising Standards
            $standards = [
                ['standard_code' => 'MS-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'standard_name' => 'نظافة الثلاجة', 'description' => 'معايير نظافة الثلاجات', 'max_score' => 100],
                ['standard_code' => 'MS-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'standard_name' => 'ترتيب الأرفف', 'description' => 'معايير ترتيب الأرفف', 'max_score' => 100],
            ];

            foreach ($standards as $s) {
                $std = MerchandisingStandard::updateOrCreate(
                    ['standard_code' => $s['standard_code'], 'company_id' => $company->id],
                    ['standard_name' => $s['standard_name'], 'description' => $s['description'], 'max_score' => $s['max_score'], 'is_active' => true]
                );

                MerchandisingStandardItem::create([
                    'merchandising_standard_id' => $std->id,
                    'item_no' => 1,
                    'item_name' => 'نظافة الجدران الداخلية',
                    'score' => 20,
                    'display_order' => 1,
                ]);
            }

            // Display Locations
            $locations = [
                ['location_code' => 'DL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'location_name' => 'الرف الرئيسي'],
                ['location_code' => 'DL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'location_name' => 'الثلاجة'],
                ['location_code' => 'DL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'location_name' => 'منطقة العروض'],
            ];

            foreach ($locations as $l) {
                DisplayLocation::updateOrCreate(
                    ['location_code' => $l['location_code'], 'company_id' => $company->id],
                    ['location_name' => $l['location_name']]
                );
            }

            // Merchandising Checklists
            $checklist = MerchandisingChecklist::updateOrCreate(
                ['check_code' => 'MC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01'],
                ['company_id' => $company->id, 'check_name' => 'نظافة الثلاجة', 'max_score' => 10, 'is_active' => true]
            );

            // Merchandising Visits
            if ($customers->isNotEmpty()) {
                $employee = \App\Models\Employee::where('company_id', $company->id)->first();
                $visit = MerchandisingVisit::create([
                    'company_id' => $company->id,
                    'sales_rep_id' => $employee?->id,
                    'customer_id' => $customers[0]->id,
                    'visit_date' => now()->toDateString(),
                    'overall_score' => 8,
                    'notes' => 'يحتاج تنظيف',
                ]);

                MerchandisingVisitDetail::create([
                    'merchandising_visit_id' => $visit->id,
                    'checklist_id' => $checklist->id,
                    'score' => 8,
                    'remarks' => 'يحتاج تنظيف',
                ]);

                $audit = MerchandisingAudit::create([
                    'company_id' => $company->id,
                    'customer_id' => $customers[0]->id,
                    'sales_rep_id' => $employee?->id,
                    'audit_date' => now()->toDateString(),
                    'overall_score' => 85,
                    'notes' => 'نظافة جيدة',
                ]);

                $standardItem = MerchandisingStandardItem::first();
                if ($standardItem) {
                    MerchandisingAuditDetail::create([
                        'merchandising_audit_id' => $audit->id,
                        'merchandising_standard_item_id' => $standardItem->id,
                        'score' => 18,
                        'remarks' => 'نظافة جيدة',
                    ]);
                }

                MerchandisingAuditPhoto::create([
                    'merchandising_audit_id' => $audit->id,
                    'photo_type' => 'STORE',
                    'file_path' => 'merchandising/sample-photo.jpg',
                ]);
            }

            // Merchandising Tasks
            $task = MerchandisingTask::updateOrCreate(
                ['company_id' => $company->id, 'task_name' => 'تنظيف الثلاجات'],
                ['description' => 'تنظيف وترتيب الثلاجات', 'is_active' => true]
            );

            if ($customers->isNotEmpty() && $employee) {
                MerchandisingTaskAssignment::create([
                    'merchandising_task_id' => $task->id,
                    'customer_id' => $customers[0]->id,
                    'sales_rep_id' => $employee->id,
                    'assigned_date' => now()->toDateString(),
                    'status' => 'PENDING',
                ]);
            }
        }
    }
}
