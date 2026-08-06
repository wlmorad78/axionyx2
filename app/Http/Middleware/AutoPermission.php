<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-resolves permissions from Laravel route names.
 *
 * Pattern: {module}.{resource}.{action}
 *
 * Route name examples:
 *   customers.index    → customer.customer.view
 *   customers.store    → customer.customer.create
 *   customers.show     → customer.customer.view
 *   customers.update   → customer.customer.edit
 *   customers.destroy  → customer.customer.delete
 *
 * Maps controller actions to permission actions:
 *   index   → view
 *   store   → create
 *   show    → view
 *   update  → edit
 *   destroy → delete
 *
 * Sub-resources inherit parent permission:
 *   customer-groups.index → customer.group.view
 */
class AutoPermission
{
    protected PermissionService $permissions;

    /**
     * Map URI segments to permission module.resource prefixes.
     * Keys are URI prefixes, values are [module, resource] pairs.
     */
    protected array $uriMap = [
        // Customers
        'customers'                => ['customer', 'customer'],
        'customer-groups'          => ['customer', 'group'],
        'customer-classes'         => ['customer', 'class'],
        'customer-types'           => ['customer', 'type'],
        'customer-contacts'        => ['customer', 'contact'],
        'customer-addresses'       => ['customer', 'contact'],
        'customer-credit-limits'   => ['customer', 'customer'],
        'customer-accounts'        => ['customer', 'customer'],
        'customer-ledger'          => ['customer', 'customer'],

        // Suppliers
        'suppliers'                => ['purchase', 'supplier'],
        'supplier-groups'          => ['supplier', 'group'],
        'supplier-contacts'        => ['supplier', 'contact'],
        'supplier-quotations'      => ['supplier', 'quotation'],
        'supplier-quotation-items' => ['supplier', 'quotation'],
        'supplier-ledger'          => ['purchase', 'supplier'],

        // Items / Inventory
        'items'                    => ['inventory', 'item'],
        'item-categories'          => ['inventory', 'category'],
        'item-sub-categories'      => ['inventory', 'sub_category'],
        'item-units'               => ['inventory', 'item_unit'],
        'item-prices'              => ['inventory', 'item'],
        'item-barcodes'            => ['inventory', 'item'],
        'item-batches'             => ['inventory', 'item'],
        'product-companies'        => ['inventory', 'item'],

        // Warehouses
        'warehouses'               => ['inventory', 'warehouse'],
        'warehouse-types'          => ['inventory', 'warehouse'],
        'warehouse-transfers'      => ['inventory', 'warehouse'],
        'warehouse-transfer-items' => ['inventory', 'warehouse'],
        'units'                    => ['inventory', 'unit'],

        // Inventory Transactions
        'inventory-transactions'       => ['inventory', 'transaction'],
        'inventory-transaction-items'  => ['inventory', 'transaction'],
        'inventory-transaction-types'  => ['inventory', 'transaction'],
        'inventory-opening-balances'   => ['inventory', 'item'],
        'stock-adjustments'            => ['inventory', 'stock_adjustment'],
        'stock-adjustment-items'       => ['inventory', 'stock_adjustment'],
        'stock-counts'                 => ['inventory', 'stock_count'],
        'stock-count-items'            => ['inventory', 'stock_count'],
        'inventory-revaluations'       => ['inventory', 'item'],
        'inventory-revaluation-items'  => ['inventory', 'item'],

        // Sales
        'sales-invoices'            => ['sales', 'invoice'],
        'sales-invoice-items'       => ['sales', 'invoice'],
        'sales-invoice-discounts'   => ['sales', 'invoice'],
        'sales-invoice-taxes'       => ['sales', 'invoice'],
        'sales-invoice-incentives'  => ['sales', 'invoice'],
        'customer-returns'          => ['sales', 'return'],
        'customer-return-items'     => ['sales', 'return'],
        'sales-targets'             => ['sales', 'target'],
        'sales-target-details'      => ['sales', 'target'],
        'sales-routes'              => ['sales', 'route'],
        'route-schedules'           => ['sales', 'route'],
        'route-customers'           => ['sales', 'route'],
        'route-visits'              => ['sales', 'route'],
        'route-templates'           => ['sales', 'route'],
        'route-stops'               => ['sales', 'route'],
        'customer-visits'           => ['sales', 'visit'],
        'sales-incentives'          => ['sales', 'target'],
        'sales-incentive-conditions' => ['sales', 'target'],
        'sales-incentive-condition-items' => ['sales', 'target'],
        'sales-incentive-rewards'   => ['sales', 'target'],
        'salesman-assignments'      => ['sales', 'route'],
        'salesman-settlements'      => ['sales', 'invoice'],
        'collections'               => ['sales', 'invoice'],

        // Purchase
        'purchase-invoices'         => ['purchase', 'invoice'],
        'purchase-invoice-items'    => ['purchase', 'invoice'],
        'purchase-orders'           => ['purchase', 'order'],
        'purchase-order-items'      => ['purchase', 'order'],
        'purchase-receipts'         => ['purchase', 'receipt'],
        'purchase-receipt-items'    => ['purchase', 'receipt'],
        'purchase-returns'          => ['purchase', 'return'],
        'purchase-return-items'     => ['purchase', 'return'],
        'purchase-expenses'         => ['purchase', 'expense'],
        'purchase-requests'         => ['purchase', 'request'],
        'purchase-request-items'    => ['purchase', 'request'],

        // Treasury
        'treasuries'                => ['treasury', 'treasury'],
        'treasury-types'            => ['treasury', 'treasury'],
        'treasury-transactions'     => ['treasury', 'transaction'],
        'treasury-adjustments'      => ['treasury', 'transaction'],
        'treasury-transfers'        => ['treasury', 'transfer'],
        'treasury-daily-closings'   => ['treasury', 'daily_closing'],
        'treasury-closing-details'  => ['treasury', 'daily_closing'],
        'treasury-shifts'           => ['treasury', 'shift'],
        'treasury-shift-transactions' => ['treasury', 'shift'],
        'treasury-counts'           => ['treasury', 'count'],
        'treasury-count-details'    => ['treasury', 'count'],
        'treasury-custodies'        => ['treasury', 'custody'],
        'treasury-custody-transactions' => ['treasury', 'custody'],
        'treasury-opening-balances'  => ['treasury', 'treasury'],
        'treasury-alerts'           => ['treasury', 'treasury'],
        'treasury-cash-limits'      => ['treasury', 'treasury'],
        'safes'                     => ['treasury', 'treasury'],
        'expense-types'             => ['treasury', 'expense'],
        'expenses'                  => ['treasury', 'expense'],

        'payment-vouchers'          => ['treasury', 'payment'],
        'receipt-vouchers'          => ['treasury', 'receipt'],

        // Accounting
        'accounts'                  => ['accounting', 'account'],
        'account-types'             => ['accounting', 'account_type'],
        'account-groups'            => ['accounting', 'account'],
        'journal-entries'           => ['accounting', 'journal'],
        'journal-entry-lines'       => ['accounting', 'journal'],
        'journal-entry-types'       => ['accounting', 'journal'],
        'opening-balances'          => ['accounting', 'opening'],
        'opening-balance-documents' => ['accounting', 'opening'],
        'manual-journal-entries'    => ['accounting', 'journal'],
        'manual-journal-entry-lines' => ['accounting', 'journal'],
        'fiscal-years'              => ['accounting', 'period'],
        'accounting-periods'        => ['accounting', 'period'],
        'cost-centers'              => ['accounting', 'cost_center'],
        'cost-center-types'         => ['accounting', 'cost_center'],
        'budgets'                   => ['accounting', 'budget'],
        'budget-lines'              => ['accounting', 'budget'],
        'bank-accounts'             => ['accounting', 'account'],
        'bank-transfers'            => ['accounting', 'account'],
        'bank-reconciliations'      => ['accounting', 'account'],

        // Tax
        'tax-types'                 => ['tax', 'type'],
        'tax-rates'                 => ['tax', 'rate'],
        'tax-groups'                => ['tax', 'group'],
        'tax-group-details'         => ['tax', 'group'],
        'tax-exemptions'            => ['tax', 'exemption'],
        'tax-rules'                 => ['tax', 'rule'],
        'tax-calculations'          => ['tax', 'rule'],
        'tax-calculation-details'   => ['tax', 'rule'],
        'tax-jurisdictions'         => ['tax', 'type'],
        'tax-periods'               => ['tax', 'type'],
        'tax-returns'               => ['tax', 'return'],
        'tax-return-details'        => ['tax', 'return'],
        'withholding-tax-certificates' => ['tax', 'withholding'],
        'customer-tax-profiles'     => ['tax', 'type'],
        'supplier-tax-profiles'     => ['tax', 'type'],
        'item-tax-profiles'         => ['tax', 'type'],

        // CRM
        'leads'                     => ['crm', 'lead'],
        'lead-activities'           => ['crm', 'lead_activity'],
        'opportunities'             => ['crm', 'opportunity'],
        'opportunity-stages'        => ['crm', 'opportunity_stage'],
        'competitors'               => ['crm', 'competitor'],
        'competitor-brands'         => ['crm', 'competitor'],
        'competitor-products'       => ['crm', 'competitor_product'],
        'competitor-price-surveys'  => ['crm', 'price_survey'],
        'competitor-price-survey-items' => ['crm', 'price_survey'],
        'competitor-promotions'     => ['crm', 'competitor'],
        'competitor-promotion-items' => ['crm', 'competitor'],
        'competitor-new-products'   => ['crm', 'competitor'],
        'competitor-photos'         => ['crm', 'competitor'],
        'competitor-shelf-items'    => ['crm', 'competitor'],
        'market-issues'             => ['crm', 'competitor'],

        // HR
        'employees'                 => ['hr', 'employee'],
        'employee-assignments'      => ['hr', 'employee'],
        'employee-contracts'        => ['hr', 'contract'],
        'employee-contract-amendments' => ['hr', 'contract'],
        'departments'               => ['hr', 'department'],
        'job-positions'             => ['hr', 'job_position'],
        'job-families'              => ['hr', 'job_position'],
        'job-titles'                => ['hr', 'job_position'],
        'job-grades'                => ['hr', 'job_position'],
        'position-levels'           => ['hr', 'job_position'],
        'leave-types'               => ['hr', 'leave'],
        'leave-requests'            => ['hr', 'leave'],
        'shift-types'               => ['hr', 'shift'],
        'shifts'                    => ['hr', 'shift'],
        'employee-shifts'           => ['hr', 'shift'],
        'attendance-statuses'       => ['hr', 'attendance'],
        'attendance-records'        => ['hr', 'attendance'],
        'attendance-adjustments'    => ['hr', 'attendance'],
        'holidays'                  => ['hr', 'attendance'],
        'employee-missions'         => ['hr', 'mission'],
        'salary-component-types'    => ['hr', 'salary_component'],
        'salary-components'         => ['hr', 'salary_component'],
        'employee-salary-structures' => ['hr', 'salary_component'],
        'salary-scales'             => ['hr', 'salary_component'],
        'payroll-periods'           => ['hr', 'payroll'],
        'payroll-runs'              => ['hr', 'payroll'],
        'payroll-run-details'       => ['hr', 'payroll'],
        'employee-loans'            => ['hr', 'loan'],
        'employee-advances'         => ['hr', 'advance'],
        'employee-penalties'        => ['hr', 'penalty'],
        'employee-rewards'          => ['hr', 'reward'],
        'employee-statuses'         => ['hr', 'employee'],
        'contract-types'            => ['hr', 'contract'],
        'contract-statuses'         => ['hr', 'contract'],

        // Assets
        'asset-categories'          => ['asset', 'category'],
        'assets'                    => ['asset', 'asset'],
        'asset-assignments'         => ['asset', 'assignment'],
        'asset-depreciations'       => ['asset', 'depreciation'],

        // Pricing
        'price-levels'              => ['pricing', 'price_level'],
        'customer-price-levels'     => ['pricing', 'price_level'],
        'customer-special-prices'   => ['pricing', 'special_price'],
        'pricing-rules'             => ['pricing', 'rule'],
        'pricing-rule-conditions'   => ['pricing', 'rule'],
        'pricing-rule-items'        => ['pricing', 'rule'],
        'pricing-methods'           => ['pricing', 'rule'],
        'quantity-price-breaks'     => ['pricing', 'rule'],
        'contract-prices'           => ['pricing', 'contract_price'],
        'pricing-calculations'      => ['pricing', 'rule'],
        'pricing-calculation-details' => ['pricing', 'rule'],
        'price-approval-requests'   => ['pricing', 'approval'],
        'price-approval-steps'      => ['pricing', 'approval'],
        'pricing-exceptions'        => ['pricing', 'rule'],
        'pricing-audit-log'         => ['pricing', 'rule'],
        'customer-price-lists'      => ['pricing', 'price_list'],

        // Marketing
        'marketing-campaigns'       => ['marketing', 'campaign'],
        'marketing-campaign-customers' => ['marketing', 'campaign'],
        'marketing-assets'          => ['marketing', 'asset'],
        'marketing-asset-categories' => ['marketing', 'asset'],
        'marketing-asset-movements'  => ['marketing', 'asset'],
        'marketing-asset-maintenance' => ['marketing', 'asset'],
        'marketing-materials'       => ['marketing', 'material'],
        'marketing-support-types'   => ['marketing', 'material'],
        'customer-marketing-supports' => ['marketing', 'material'],
        'customer-marketing-assets'  => ['marketing', 'asset'],
        'customer-marketing-materials' => ['marketing', 'material'],
        'customer-agreements'       => ['marketing', 'agreement'],
        'customer-agreement-types'  => ['marketing', 'agreement'],
        'customer-agreement-items'  => ['marketing', 'agreement'],
        'customer-agreement-targets' => ['marketing', 'agreement'],
        'customer-agreement-payments' => ['marketing', 'agreement'],
        'customer-agreement-history' => ['marketing', 'agreement'],
        'customer-rebate-rules'     => ['marketing', 'agreement'],

        // Merchandising
        'merchandising-visits'       => ['merchandising', 'visit'],
        'merchandising-visit-details' => ['merchandising', 'visit'],
        'merchandising-audits'       => ['merchandising', 'audit'],
        'merchandising-audit-details' => ['merchandising', 'audit'],
        'merchandising-audit-photos' => ['merchandising', 'audit'],
        'merchandising-tasks'        => ['merchandising', 'task'],
        'merchandising-task-assignments' => ['merchandising', 'task'],
        'merchandising-standards'    => ['merchandising', 'standard'],
        'merchandising-standard-items' => ['merchandising', 'standard'],
        'merchandising-photos'       => ['merchandising', 'photo'],
        'merchandising-checklists'   => ['merchandising', 'checklist'],
        'shelf-audits'               => ['merchandising', 'shelf'],
        'shelf-audit-items'          => ['merchandising', 'shelf'],
        'shelf-share-surveys'        => ['merchandising', 'shelf'],
        'shelf-share-items'          => ['merchandising', 'shelf'],
        'display-locations'          => ['merchandising', 'standard'],
        'availability-audits'        => ['merchandising', 'audit'],
        'refrigerator-audits'        => ['merchandising', 'audit'],
        'posm-audits'                => ['merchandising', 'audit'],

        // Surveys
        'surveys'                    => ['survey', 'survey'],
        'survey-categories'          => ['survey', 'category'],
        'survey-questions'           => ['survey', 'question'],
        'survey-question-options'    => ['survey', 'question'],
        'survey-question-rules'      => ['survey', 'question'],
        'survey-responses'           => ['survey', 'response'],
        'survey-response-answers'    => ['survey', 'response'],
        'survey-response-options'    => ['survey', 'response'],
        'survey-response-photos'     => ['survey', 'response'],
        'survey-scoring-rules'       => ['survey', 'score'],
        'survey-scores'              => ['survey', 'score'],
        'survey-assignments'         => ['survey', 'survey'],

        // Distribution
        'load-requests'              => ['distribution', 'load_request'],
        'load-request-items'         => ['distribution', 'load_request'],
        'issue-orders'               => ['distribution', 'issue_order'],
        'issue-order-items'          => ['distribution', 'issue_order'],
        'return-orders'              => ['distribution', 'return_order'],
        'return-order-items'         => ['distribution', 'return_order'],
        'distribution-plans'         => ['distribution', 'plan'],

        // Vehicles
        'vehicles'                   => ['vehicle', 'vehicle'],
        'vehicle-types'              => ['vehicle', 'type'],
        'drivers'                    => ['vehicle', 'driver'],
        'vehicle-assignments'        => ['vehicle', 'assignment'],
        'vehicle-fuel-transactions'  => ['vehicle', 'fuel'],
        'vehicle-maintenance'        => ['vehicle', 'maintenance'],
        'vehicle-expenses'           => ['vehicle', 'expense'],
        'vehicle-loadings'           => ['vehicle', 'loading'],
        'vehicle-warehouses'         => ['vehicle', 'vehicle'],
        'vehicle-inventory-transactions' => ['vehicle', 'vehicle'],
        'vehicle-inventory-transaction-items' => ['vehicle', 'vehicle'],
        'vehicle-stock-balances'     => ['vehicle', 'vehicle'],
        'vehicle-loads'              => ['vehicle', 'loading'],
        'vehicle-load-items'         => ['vehicle', 'loading'],
        'vehicle-unloads'            => ['vehicle', 'loading'],
        'vehicle-unload-items'       => ['vehicle', 'loading'],
        'vehicle-cash-accounts'      => ['vehicle', 'vehicle'],
        'vehicle-cash-transactions'  => ['vehicle', 'vehicle'],
        'vehicle-daily-expenses'     => ['vehicle', 'daily_expense'],
        'vehicle-stock-counts'       => ['vehicle', 'vehicle'],
        'vehicle-stock-count-items'  => ['vehicle', 'vehicle'],
        'vehicle-settlements'        => ['vehicle', 'settlement'],
        'vehicle-settlement-items'   => ['vehicle', 'settlement'],
        'vehicle-deposits'           => ['vehicle', 'vehicle'],
        'gps-tracking-sessions'      => ['vehicle', 'gps'],
        'gps-tracking-points'        => ['vehicle', 'gps'],

        // Settings
        'branches'                   => ['settings', 'branch'],
        'users'                      => ['settings', 'user'],
        'roles'                      => ['settings', 'role'],
        'currencies'                 => ['settings', 'currency'],
        'payment-methods'            => ['settings', 'payment_method'],
        'countries'                  => ['settings', 'country'],
        'governorates'               => ['settings', 'governorate'],
        'cities'                     => ['settings', 'city'],
        'districts'                  => ['settings', 'district'],
        'streets'                    => ['settings', 'district'],
        'companies'                  => ['settings', 'company'],

        // E-Invoicing
        'e-invoice-providers'        => ['einvoice', 'provider'],
        'e-invoice-transactions'     => ['einvoice', 'transaction'],

        // Notifications
        'notification-types'         => ['notification', 'type'],
        'notification-channels'      => ['notification', 'type'],
        'notification-events'        => ['notification', 'type'],
        'notification-rules'         => ['notification', 'rule'],
        'notification-rule-recipients' => ['notification', 'rule'],
        'notification-recipients'    => ['notification', 'type'],
        'notification-deliveries'    => ['notification', 'log'],
        'notification-preferences'   => ['notification', 'type'],
        'notification-groups'        => ['notification', 'type'],
        'notification-group-members' => ['notification', 'type'],
        'notification-templates'     => ['notification', 'template'],
        'notifications'              => ['notification', 'log'],
        'notification-queue'         => ['notification', 'queue'],
        'alert-rules'                => ['notification', 'rule'],
        'alerts'                     => ['notification', 'log'],
        'alert-actions'              => ['notification', 'log'],
        'scheduled-notifications'    => ['notification', 'rule'],

        // Integrations
        'integration-providers'      => ['integration', 'provider'],
        'integration-accounts'       => ['integration', 'provider'],
        'integration-endpoints'      => ['integration', 'endpoint'],
        'integration-event-subscriptions' => ['integration', 'webhook'],
        'webhook-endpoints'          => ['integration', 'webhook'],
        'webhook-subscriptions'      => ['integration', 'webhook'],
        'webhook-logs'               => ['integration', 'log'],
        'api-clients'                => ['integration', 'api_client'],
        'api-tokens'                 => ['integration', 'api_client'],
        'api-logs'                   => ['integration', 'log'],
        'api-rate-limits'            => ['integration', 'log'],
        'api-permissions'            => ['integration', 'log'],
        'api-request-logs'           => ['integration', 'log'],

        // Audit
        'audit-logs'                 => ['audit', 'log'],
        'login-logs'                 => ['audit', 'log'],

        // Documents
        'document-categories'        => ['settings', 'company'],
        'documents'                  => ['settings', 'company'],

        // Workflows
        'workflow-definitions'       => ['settings', 'role'],
        'workflow-steps'             => ['settings', 'role'],
        'workflow-instances'         => ['settings', 'role'],
        'workflow-instance-steps'    => ['settings', 'role'],
        'workflow-actions'           => ['settings', 'role'],
        'workflow-templates'         => ['settings', 'role'],
        'workflow-template-steps'    => ['settings', 'role'],
        'workflow-roles'             => ['settings', 'role'],
        'workflow-sla-rules'         => ['settings', 'role'],
        'workflow-types'             => ['settings', 'role'],
        'workflow-delegations'       => ['settings', 'role'],
        'workflow-escalations'       => ['settings', 'role'],
        'workflow-conditions'        => ['settings', 'role'],
        'workflow-notifications'     => ['settings', 'role'],
        'workflow-action-logs'       => ['settings', 'role'],
        'approval-requests'          => ['settings', 'role'],
        'approval-actions'           => ['settings', 'role'],

        // Sync
        'sync-batches'               => ['integration', 'log'],
        'sync-logs'                  => ['integration', 'log'],
        'mobile-devices'             => ['integration', 'log'],

        // Master Data
        'master-data-request-types'  => ['settings', 'company'],
        'master-data-requests'       => ['settings', 'company'],
        'master-data-request-steps'  => ['settings', 'company'],
        'master-data-request-history' => ['settings', 'company'],
        'master-data-workflows'      => ['settings', 'company'],
        'master-data-workflow-steps' => ['settings', 'company'],

        // Message
        'message-templates'          => ['notification', 'template'],
        'message-logs'               => ['notification', 'log'],

        // KPI
        'kpi-definitions'            => ['hr', 'employee'],
        'kpi-targets'                => ['hr', 'employee'],
        'kpi-results'                => ['hr', 'employee'],

        // Demand Forecast
        'demand-forecasts'           => ['inventory', 'item'],
        'forecast-history'           => ['inventory', 'item'],
        'replenishment-rules'        => ['inventory', 'item'],
        'replenishment-suggestions'  => ['inventory', 'item'],

        // Subscription
        'subscription-plans'         => ['settings', 'company'],
        'company-subscriptions'      => ['settings', 'company'],
        'company-subscription-limits' => ['settings', 'company'],

        // Dashboard
        'dashboard'                  => ['dashboard', 'view'],

        // Reports
        'reports'                    => ['reports', 'view'],
    ];

    /**
     * Controller action → permission action mapping.
     */
    protected array $actionMap = [
        'index'    => 'view',
        'store'    => 'create',
        'show'     => 'view',
        'update'   => 'edit',
        'destroy'  => 'delete',
        'create'   => 'view',
        'edit'     => 'view',
        'approve'  => 'edit',
        'reject'   => 'edit',
    ];

    public function __construct(PermissionService $permissions)
    {
        $this->permissions = $permissions;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            // Allow public routes (login, forgot-password, etc.) without auth
            $path = $request->path();
            if (str_starts_with($path, 'api/login') || str_starts_with($path, 'api/forgot-password') || str_starts_with($path, 'api/handheld/hh-login')) {
                return $next($request);
            }
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Admin bypasses all permission checks
        if ($this->permissions->check($user, '*')) {
            return $next($request);
        }

        // Resolve permission from route
        $permission = $this->resolvePermission($request);

        if ($permission !== null) {
            if (!$this->permissions->check($user, $permission)) {
                return response()->json([
                    'message' => 'Unauthorized: insufficient permissions',
                    'required' => $permission,
                    'permission' => $permission,
                ], 403);
            }
        }

        return $next($request);
    }

    protected function resolvePermission(Request $request): ?string
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        // Get route name (e.g., 'customers.index', 'reports.sales')
        $routeName = $route->getName();

        if (!$routeName) {
            return null;
        }

        // Split into parts: ['customers', 'index'] or ['reports', 'sales']
        $parts = explode('.', $routeName);

        if (count($parts) < 2) {
            return null;
        }

        $uri = $parts[0];
        $action = end($parts);

        // Skip non-CRUD routes (api.*)
        if ($uri === 'api' || in_array($action, ['create', 'edit'])) {
            return null;
        }

        // Special handling for dashboard
        if ($uri === 'dashboard') {
            return 'dashboard.view';
        }

        // Special handling for reports
        if ($uri === 'reports') {
            $reportPerms = [
                'sales'     => 'reports.sales.view',
                'purchases' => 'reports.purchase.view',
                'inventory' => 'reports.inventory.view',
                'profit'    => 'reports.profit.view',
            ];
            return $reportPerms[$action] ?? 'reports.sales.view';
        }

        // Map URI to permission prefix
        $mapped = $this->uriMap[$uri] ?? null;

        if (!$mapped) {
            // Unknown resource — skip permission check (allows new routes without breaking)
            return null;
        }

        [$module, $resource] = $mapped;

        // Map action to permission action
        $permAction = $this->actionMap[$action] ?? null;

        if (!$permAction) {
            return null;
        }

        return "{$module}.{$resource}.{$permAction}";
    }
}
