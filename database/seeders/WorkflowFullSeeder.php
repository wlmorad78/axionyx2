<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowDelegation;
use App\Models\WorkflowEscalation;
use App\Models\WorkflowInstance;
use App\Models\WorkflowInstanceStep;
use App\Models\WorkflowNotification;
use App\Models\WorkflowRole;
use App\Models\WorkflowSlaRule;
use App\Models\WorkflowStep;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTemplateStep;
use App\Models\WorkflowType;
use Illuminate\Database\Seeder;

class WorkflowFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $adminUser = User::where('company_id', $company->id)->first();

            // Workflow Types
            $wTypes = [
                ['workflow_code' => 'WT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-APPROVAL', 'workflow_name' => 'الموافقة على المشتريات - Purchase Approval', 'entity_type' => 'PurchaseOrder'],
                ['workflow_code' => 'WT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-DISCOUNT', 'workflow_name' => 'موافقة الخصومات - Discount Approval', 'entity_type' => 'SalesInvoice'],
                ['workflow_code' => 'WT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-MASTER', 'workflow_name' => 'Master Data Approval', 'entity_type' => 'Customer'],
            ];

            foreach ($wTypes as $wt) {
                WorkflowType::updateOrCreate(
                    ['workflow_code' => $wt['workflow_code']],
                    ['company_id' => $company->id, 'workflow_name' => $wt['workflow_name'], 'entity_type' => $wt['entity_type'], 'is_active' => true]
                );
            }

            // Workflows
            $wt = WorkflowType::where('workflow_code', 'LIKE', 'WT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '%')->first();
            $workflow = Workflow::updateOrCreate(
                ['workflow_name' => 'سير عمل الموافقة على المشتريات - Purchase Approval Workflow'],
                [
                    'company_id' => $company->id,
                    'workflow_type_id' => $wt?->id,
                    'priority' => 1,
                    'effective_from' => '2026-01-01',
                    'status' => 'ACTIVE',
                ]
            );

            // Workflow Steps (workflow_steps requires workflow_definition_id, but we use workflow_id via alter)
            // We need a workflow_definition first - check if it exists or create one
            $workflowDefinition = \App\Models\WorkflowDefinition::where('company_id', $company->id)->first();
            if (!$workflowDefinition) {
                $workflowDefinition = \App\Models\WorkflowDefinition::create([
                    'company_id' => $company->id,
                    'workflow_code' => 'WD-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001',
                    'workflow_name' => 'تعريف الموافقة على المشتريات - Purchase Approval Definition',
                    'module_name' => 'PurchaseOrder',
                    'is_active' => true,
                ]);
            }

            $step1 = WorkflowStep::create([
                'workflow_definition_id' => $workflowDefinition->id,
                'workflow_id' => $workflow->id,
                'step_name' => 'موافقة المدير المباشر - Direct Manager Approval',
                'step_no' => 1,
                'is_mandatory' => true,
                'allow_delegate' => false,
            ]);

            $step2 = WorkflowStep::create([
                'workflow_definition_id' => $workflowDefinition->id,
                'workflow_id' => $workflow->id,
                'step_name' => 'موافقة المدير المالي - Finance Manager Approval',
                'step_no' => 2,
                'is_mandatory' => true,
                'allow_delegate' => false,
            ]);

            // Workflow Conditions
            WorkflowCondition::create([
                'workflow_id' => $workflow->id,
                'field_name' => 'amount',
                'operator' => '>',
                'field_value' => '10000',
            ]);

            // Workflow Roles
            $role = \App\Models\Role::first();
            if ($role) {
                WorkflowRole::create([
                    'workflow_id' => $workflow->id,
                    'role_id' => $role->id,
                    'can_approve' => true,
                    'can_reject' => true,
                    'can_return' => false,
                ]);
            }

            // Workflow SLA Rules
            WorkflowSlaRule::create([
                'workflow_id' => $workflow->id,
                'step_no' => 1,
                'target_hours' => 24,
                'warning_hours' => 20,
            ]);

            // Workflow Instances
            $instance = WorkflowInstance::updateOrCreate(
                ['instance_no' => 'WI-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'workflow_id' => $workflow->id,
                    'entity_type' => 'PurchaseOrder',
                    'entity_id' => 1,
                    'status' => 'PENDING',
                    'started_by' => $adminUser?->id,
                    'started_at' => now(),
                ]
            );

            WorkflowInstanceStep::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step1->id,
                'assigned_to' => $adminUser?->id,
                'status' => 'PENDING',
            ]);

            // Workflow Delegations
            WorkflowDelegation::create([
                'from_user_id' => $adminUser?->id,
                'to_user_id' => $adminUser?->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'status' => 'ACTIVE',
            ]);

            // Workflow Escalations
            WorkflowEscalation::create([
                'workflow_step_id' => $step1->id,
                'after_hours' => 48,
            ]);

            // Workflow Notifications
            WorkflowNotification::create([
                'workflow_instance_id' => $instance->id,
                'user_id' => $adminUser?->id,
                'notification_type' => 'step_assigned',
                'status' => 'PENDING',
            ]);

            // Workflow Templates
            $template = WorkflowTemplate::updateOrCreate(
                ['template_name' => 'قالب الموافقة على المشتريات - Purchase Approval Template'],
                [
                    'company_id' => $company->id,
                    'entity_type' => 'PurchaseOrder',
                    'description' => 'قالب جاهز لسير عمل الموافقة على المشتريات',
                ]
            );

            WorkflowTemplateStep::create([
                'workflow_template_id' => $template->id,
                'step_no' => 1,
                'is_mandatory' => true,
            ]);
        }
    }
}
