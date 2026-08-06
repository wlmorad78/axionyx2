<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    private array $features = [
        // Core
        ['code' => 'customers',        'name' => 'Customers',         'name_ar' => 'العملاء',           'category' => 'core', 'sort_order' => 1],
        ['code' => 'items',            'name' => 'Items & Inventory', 'name_ar' => 'الأصناف والمخزون',  'category' => 'core', 'sort_order' => 2],
        ['code' => 'sales',            'name' => 'Sales Invoices',    'name_ar' => 'فواتير المبيعات',   'category' => 'core', 'sort_order' => 3],
        ['code' => 'collections',      'name' => 'Collections',       'name_ar' => 'التحصيل',           'category' => 'core', 'sort_order' => 4],
        ['code' => 'reports',          'name' => 'Reports',           'name_ar' => 'التقارير',          'category' => 'core', 'sort_order' => 5],
        ['code' => 'settings',         'name' => 'General Settings',  'name_ar' => 'الإعدادات العامة',   'category' => 'core', 'sort_order' => 6],

        // Distribution
        ['code' => 'suppliers',        'name' => 'Suppliers',         'name_ar' => 'الموردين',          'category' => 'distribution', 'sort_order' => 10],
        ['code' => 'purchases',        'name' => 'Purchase Invoices', 'name_ar' => 'فواتير المشتريات',  'category' => 'distribution', 'sort_order' => 11],
        ['code' => 'distribution',     'name' => 'Distribution',      'name_ar' => 'التوزيع',           'category' => 'distribution', 'sort_order' => 12],
        ['code' => 'pricing',          'name' => 'Pricing',           'name_ar' => 'التسعير',           'category' => 'distribution', 'sort_order' => 13],
        ['code' => 'vehicle_loading',  'name' => 'Vehicle Loading',   'name_ar' => 'تحميل السيارات',    'category' => 'distribution', 'sort_order' => 14],

        // Warehouse
        ['code' => 'warehouses',       'name' => 'Warehouses',        'name_ar' => 'المخازن',           'category' => 'warehouse', 'sort_order' => 20],
        ['code' => 'stock_transfers',  'name' => 'Stock Transfers',   'name_ar' => 'التحويلات المخزنية','category' => 'warehouse', 'sort_order' => 21],
        ['code' => 'stock_counts',     'name' => 'Stock Counts',      'name_ar' => 'جرد المخزون',       'category' => 'warehouse', 'sort_order' => 22],

        // Finance
        ['code' => 'treasury',         'name' => 'Treasury',          'name_ar' => 'الخزينة',           'category' => 'finance', 'sort_order' => 30],
        ['code' => 'accounting',       'name' => 'Accounting',        'name_ar' => 'المحاسبة',          'category' => 'finance', 'sort_order' => 31],
        ['code' => 'tax',              'name' => 'Tax',               'name_ar' => 'الضرائب',           'category' => 'finance', 'sort_order' => 32],
        ['code' => 'banking',          'name' => 'Banking',           'name_ar' => 'البنوك',            'category' => 'finance', 'sort_order' => 33],

        // HR
        ['code' => 'hr',               'name' => 'Human Resources',   'name_ar' => 'الموارد البشرية',    'category' => 'hr', 'sort_order' => 40],
        ['code' => 'attendance',       'name' => 'Attendance',        'name_ar' => 'الحضور والانصراف',  'category' => 'hr', 'sort_order' => 41],
        ['code' => 'payroll',          'name' => 'Payroll',           'name_ar' => 'المرتبات',          'category' => 'hr', 'sort_order' => 42],
        ['code' => 'leave',            'name' => 'Leave Management',  'name_ar' => 'إدارة الإجازات',    'category' => 'hr', 'sort_order' => 43],

        // Fleet
        ['code' => 'fleet',            'name' => 'Fleet Management',  'name_ar' => 'إدارة الأسطول',     'category' => 'fleet', 'sort_order' => 50],
        ['code' => 'vehicle_tracking', 'name' => 'Vehicle Tracking',  'name_ar' => 'تتبع GPS',          'category' => 'fleet', 'sort_order' => 51],
        ['code' => 'fuel',             'name' => 'Fuel Management',   'name_ar' => 'إدارة الوقود',      'category' => 'fleet', 'sort_order' => 52],

        // CRM
        ['code' => 'crm',              'name' => 'CRM',               'name_ar' => 'إدارة العملاء',     'category' => 'crm', 'sort_order' => 60],
        ['code' => 'merchandising',    'name' => 'Merchandising',     'name_ar' => 'التسوق',            'category' => 'crm', 'sort_order' => 61],
        ['code' => 'surveys',          'name' => 'Surveys',           'name_ar' => 'الاستبيانات',       'category' => 'crm', 'sort_order' => 62],

        // Enterprise
        ['code' => 'multi_company',    'name' => 'Multi Company',     'name_ar' => 'شركات متعددة',      'category' => 'enterprise', 'sort_order' => 70],
        ['code' => 'cost_centers',     'name' => 'Cost Centers',      'name_ar' => 'مراكز التكلفة',     'category' => 'enterprise', 'sort_order' => 71],
        ['code' => 'workflow',         'name' => 'Workflow',          'name_ar' => 'سير العمل',         'category' => 'enterprise', 'sort_order' => 72],
        ['code' => 'approval',         'name' => 'Approval Workflow', 'name_ar' => 'الموافقات',         'category' => 'enterprise', 'sort_order' => 73],
        ['code' => 'bi_dashboard',     'name' => 'BI Dashboard',      'name_ar' => 'لوحة BI',           'category' => 'enterprise', 'sort_order' => 74],
        ['code' => 'api_access',       'name' => 'API Access',        'name_ar' => 'الوصول لـ API',     'category' => 'enterprise', 'sort_order' => 75],
        ['code' => 'e_invoice',        'name' => 'E-Invoicing',       'name_ar' => 'الفوترة الإلكترونية','category' => 'enterprise', 'sort_order' => 76],

        // Platform
        ['code' => 'notifications',    'name' => 'Notifications',     'name_ar' => 'الإشعارات',         'category' => 'platform', 'sort_order' => 80],
        ['code' => 'audit_log',        'name' => 'Audit Log',         'name_ar' => 'سجل التدقيق',      'category' => 'platform', 'sort_order' => 81],
    ];

    // Plan → features mapping (tier → feature codes enabled)
    private array $planFeatures = [
        1 => ['customers', 'items', 'sales', 'collections', 'reports', 'settings'],
        2 => ['customers', 'items', 'sales', 'collections', 'reports', 'settings', 'suppliers', 'purchases', 'distribution', 'pricing', 'vehicle_loading'],
        3 => ['customers', 'items', 'sales', 'collections', 'reports', 'settings', 'suppliers', 'purchases', 'distribution', 'pricing', 'vehicle_loading', 'warehouses', 'stock_transfers', 'stock_counts', 'treasury', 'fleet', 'crm', 'merchandising'],
        4 => ['customers', 'items', 'sales', 'collections', 'reports', 'settings', 'suppliers', 'purchases', 'distribution', 'pricing', 'vehicle_loading', 'warehouses', 'stock_transfers', 'stock_counts', 'treasury', 'accounting', 'tax', 'banking', 'fleet', 'crm', 'merchandising', 'surveys', 'hr', 'attendance', 'payroll', 'leave', 'vehicle_tracking', 'fuel', 'workflow', 'approval', 'notifications', 'audit_log'],
        5 => ['customers', 'items', 'sales', 'collections', 'reports', 'settings', 'suppliers', 'purchases', 'distribution', 'pricing', 'vehicle_loading', 'warehouses', 'stock_transfers', 'stock_counts', 'treasury', 'accounting', 'tax', 'banking', 'fleet', 'crm', 'merchandising', 'surveys', 'hr', 'attendance', 'payroll', 'leave', 'vehicle_tracking', 'fuel', 'workflow', 'approval', 'notifications', 'audit_log', 'cost_centers', 'bi_dashboard', 'api_access', 'e_invoice'],
        6 => 'all',
    ];

    public function run(): void
    {
        foreach ($this->features as $f) {
            Feature::updateOrCreate(['code' => $f['code']], $f);
        }

        $plans = SubscriptionPlan::all();
        $allFeatureIds = Feature::pluck('id')->toArray();

        foreach ($plans as $plan) {
            $enabledCodes = $this->planFeatures[$plan->tier] ?? [];

            if ($enabledCodes === 'all') {
                $syncData = [];
                foreach ($allFeatureIds as $fid) {
                    $syncData[$fid] = ['is_enabled' => true, 'created_at' => now(), 'updated_at' => now()];
                }
                $plan->features()->sync($syncData);
            } else {
                $featureIds = Feature::whereIn('code', $enabledCodes)->pluck('id')->toArray();
                $syncData = [];
                foreach ($featureIds as $fid) {
                    $syncData[$fid] = ['is_enabled' => true, 'created_at' => now(), 'updated_at' => now()];
                }
                $plan->features()->sync($syncData);
            }
        }
    }
}
