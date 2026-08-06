<?php

namespace Database\Seeders;

use App\Models\AdminModule;
use App\Models\AdminScreen;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    private array $screenPermissions = [
        // ═══════════════════════════════════════════
        // Level 1: Starter — الموزع المستقل
        // ═══════════════════════════════════════════
        1 => [
            'home'               => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'customers'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'inventory'          => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'sales'              => ['can_view' => true, 'can_create' => true, 'can_edit' => false, 'can_delete' => false],
            'general-settings'   => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'reports'            => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'subscription'       => ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ],

        // ═══════════════════════════════════════════
        // Level 2: Growth — تاجر الجملة
        // ═══════════════════════════════════════════
        2 => [
            'home'               => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'customers'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'inventory'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'sales'              => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'purchases'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'hr'                 => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'distribution'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'finance'            => ['can_view' => true, 'can_create' => true, 'can_edit' => false, 'can_delete' => false],
            'general-settings'   => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'reports'            => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'subscription'       => ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ],

        // ═══════════════════════════════════════════
        // Level 3: Professional — التوكيل
        // ═══════════════════════════════════════════
        3 => [
            'home'               => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'customers'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'inventory'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'sales'              => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'purchases'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'hr'                 => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'distribution'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'finance'            => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'system-admin'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'general-settings'   => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'reports'            => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'subscription'       => ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ],

        // ═══════════════════════════════════════════
        // Level 4: Enterprise — شركة صغيرة
        // ═══════════════════════════════════════════
        4 => [
            'home'               => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'customers'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'inventory'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'sales'              => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'purchases'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'distribution'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'finance'            => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'hr'                 => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'system-admin'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'general-settings'   => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'reports'            => ['can_view' => true, 'can_create' => true, 'can_edit' => false, 'can_delete' => false],
            'subscription'       => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ],

        // ═══════════════════════════════════════════
        // Level 5: Corporate — شركة متوسطة
        // ═══════════════════════════════════════════
        5 => [
            'home'               => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'customers'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'inventory'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'sales'              => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'purchases'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'distribution'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'finance'            => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'hr'                 => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'system-admin'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'general-settings'   => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'reports'            => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
            'subscription'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false],
        ],

        // ═══════════════════════════════════════════
        // Level 6: Corporate Elite — شركة ضخمة
        // ═══════════════════════════════════════════
        6 => [
            'home'               => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'customers'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'inventory'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'sales'              => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'purchases'          => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'distribution'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'finance'            => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'hr'                 => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'system-admin'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'general-settings'   => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'reports'            => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'subscription'       => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
        ],
    ];

    public function run(): void
    {
        $plans = [
            [
                'code' => 'starter', 'name' => 'Starter', 'tier' => 1, 'package_name' => 'starter',
                'duration_months' => 12, 'price' => 6000, 'monthly_price' => 500, 'setup_price' => 5000,
                'max_branches' => 1, 'max_warehouses' => 1, 'max_treasuries' => 1, 'max_users' => 2,
                'description' => 'للموزع المستقل (عربية أو تروسيكل) — العملاء والمنتجات والمخزون والمبيعات والتحصيل.',
                'features' => ['العملاء', 'المنتجات', 'المخزون', 'المبيعات', 'التحصيل', 'المصروفات', 'كشف حساب العملاء', 'الأرباح والخسائر', 'عدد المستخدمين: 1-2'],
                'grace_period_days' => 5, 'is_active' => true, 'is_popular' => false, 'sort_order' => 1,
                'limits' => ['users' => 2, 'branches' => 1, 'warehouses' => 1, 'treasuries' => 1, 'companies' => 1, 'storage_gb' => 1, 'api_requests' => 0],
                'permission_patterns' => ['customer.*', 'inventory.item.*', 'sales.invoice.*', 'sales.return.*', 'treasury.*', 'reports.*'],
            ],
            [
                'code' => 'growth', 'name' => 'Growth', 'tier' => 2, 'package_name' => 'growth',
                'duration_months' => 12, 'price' => 24000, 'monthly_price' => 2000, 'setup_price' => 15000,
                'max_branches' => 1, 'max_warehouses' => 3, 'max_treasuries' => 2, 'max_users' => 15,
                'description' => 'لتاجر الجملة (1 إلى 10 عربيات) — إدارة المندوبين وتحميل السيارات والعمولات والموارد البشرية.',
                'features' => ['كل ما في Starter', 'إدارة مندوبين', 'تحميل السيارات', 'تصفية السيارات', 'جرد السيارة', 'متابعة التحصيل', 'مستويات أسعار', 'عروض وخصومات', 'حساب عمولات المندوبين', 'الموارد البشرية', 'عدد المستخدمين: 3-15'],
                'grace_period_days' => 5, 'is_active' => true, 'is_popular' => true, 'sort_order' => 2,
                'limits' => ['users' => 15, 'branches' => 1, 'warehouses' => 3, 'treasuries' => 2, 'companies' => 1, 'storage_gb' => 5, 'api_requests' => 1000],
                'permission_patterns' => ['customer.*', 'inventory.*', 'sales.*', 'purchase.*', 'distribution.*', 'treasury.*', 'reports.*', 'pricing.*'],
            ],
            [
                'code' => 'professional', 'name' => 'Professional', 'tier' => 3, 'package_name' => 'professional',
                'duration_months' => 12, 'price' => 84000, 'monthly_price' => 7000, 'setup_price' => 50000,
                'max_branches' => 3, 'max_warehouses' => 10, 'max_treasuries' => 5, 'max_users' => 40,
                'description' => 'للتوكيل (10 إلى 20 سيارة) — مخازن متعددة وصلاحيات متقدمة وGPS وتطبيق أندرويد والموارد البشرية.',
                'features' => ['كل ما في Growth', 'مخازن متعددة', 'تحويلات مخزنية', 'صلاحيات متقدمة', 'إدارة الموردين', 'المشتريات', 'المرتجعات', 'تتبع GPS', 'تطبيق أندرويد للمندوب', 'تقارير الأداء', 'الموارد البشرية', 'عدد المستخدمين: 15-40'],
                'grace_period_days' => 7, 'is_active' => true, 'is_popular' => false, 'sort_order' => 3,
                'limits' => ['users' => 40, 'branches' => 3, 'warehouses' => 10, 'treasuries' => 5, 'companies' => 1, 'storage_gb' => 20, 'api_requests' => 5000],
                'permission_patterns' => ['customer.*', 'supplier.*', 'inventory.*', 'sales.*', 'purchase.*', 'distribution.*', 'treasury.*', 'accounting.*', 'reports.*', 'pricing.*', 'tax.*', 'settings.*'],
            ],
            [
                'code' => 'enterprise', 'name' => 'Enterprise', 'tier' => 4, 'package_name' => 'enterprise',
                'duration_months' => 12, 'price' => 180000, 'monthly_price' => 10000, 'setup_price' => 100000,
                'max_branches' => 10, 'max_warehouses' => 20, 'max_treasuries' => 10, 'max_users' => 100,
                'description' => 'لشركة صغيرة (فروع ومخازن ومندوبين) — إدارة الفروع والموارد البشرية والمرتبات.',
                'features' => ['كل ما في Professional', 'إدارة الفروع', 'الموارد البشرية', 'الحضور والانصراف', 'المرتبات', 'إدارة السيارات', 'الصيانة', 'الوقود', 'الموافقات', 'لوحة مؤشرات KPI', 'عدد المستخدمين: 30-100'],
                'grace_period_days' => 10, 'is_active' => true, 'is_popular' => false, 'sort_order' => 4,
                'limits' => ['users' => 100, 'branches' => 10, 'warehouses' => 20, 'treasuries' => 10, 'companies' => 1, 'storage_gb' => 50, 'api_requests' => 10000],
                'permission_patterns' => ['*'],
            ],
            [
                'code' => 'corporate', 'name' => 'Corporate', 'tier' => 5, 'package_name' => 'corporate',
                'duration_months' => 12, 'price' => 480000, 'monthly_price' => 25000, 'setup_price' => 300000,
                'max_branches' => 30, 'max_warehouses' => 50, 'max_treasuries' => 20, 'max_users' => 300,
                'description' => 'لشركة متوسطة — الحسابات العامة الكاملة ومراكز التكلفة وإدارة الأصول وWorkflow وBI.',
                'features' => ['كل ما في Enterprise', 'الحسابات العامة الكاملة', 'مراكز التكلفة', 'إدارة الأصول', 'إدارة العقود', 'Workflow', 'BI Dashboard', 'API Integration', 'E-Invoice', 'عدد المستخدمين: 100-300'],
                'grace_period_days' => 15, 'is_active' => true, 'is_popular' => false, 'sort_order' => 5,
                'limits' => ['users' => 300, 'branches' => 30, 'warehouses' => 50, 'treasuries' => 20, 'companies' => 3, 'storage_gb' => 200, 'api_requests' => 50000],
                'permission_patterns' => ['*'],
            ],
            [
                'code' => 'corporate-elite', 'name' => 'Corporate Elite', 'tier' => 6, 'package_name' => 'corporate-elite',
                'duration_months' => 12, 'price' => 1800000, 'monthly_price' => 100000, 'setup_price' => 700000,
                'max_branches' => 999, 'max_warehouses' => 999, 'max_treasuries' => 999, 'max_users' => 999,
                'description' => 'لشركة ضخمة — Multi Company وData Warehouse وBusiness Intelligence وAI Forecasting.',
                'features' => ['كل ما في Corporate', 'Multi Company', 'Multi Branch', 'Data Warehouse', 'Business Intelligence', 'Route Optimization', 'AI Forecasting', 'إدارة آلاف العملاء', 'إدارة مئات المندوبين', 'High Availability', 'عدد المستخدمين: 300+'],
                'grace_period_days' => 30, 'is_active' => true, 'is_popular' => false, 'sort_order' => 6,
                'limits' => ['users' => 999, 'branches' => 999, 'warehouses' => 999, 'treasuries' => 999, 'companies' => 99, 'storage_gb' => 999, 'api_requests' => 999999],
                'permission_patterns' => ['*'],
            ],
        ];

        foreach ($plans as $planData) {
            $plan = SubscriptionPlan::updateOrCreate(
                ['code' => $planData['code']],
                collect($planData)->except(['limits', 'permission_patterns'])->toArray()
            );

            $this->syncModulesForPlan($plan);
            $this->syncLimitsForPlan($plan, $planData['limits'] ?? []);
            $this->syncPermissionsForPlan($plan, $planData['permission_patterns'] ?? []);
        }
    }

    private function syncModulesForPlan(SubscriptionPlan $plan): void
    {
        $permissions = $this->screenPermissions[$plan->tier] ?? [];
        $syncData = [];

        foreach ($permissions as $moduleKey => $perms) {
            $module = AdminModule::where('key', $moduleKey)->first();
            if (!$module) {
                continue;
            }

            $syncData[$module->id] = [
                'can_view'    => $perms['can_view'],
                'can_create'  => $perms['can_create'],
                'can_edit'    => $perms['can_edit'],
                'can_delete'  => $perms['can_delete'],
                'sort_order'  => $module->sort_order,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        $plan->modules()->sync($syncData);
    }

    private function syncLimitsForPlan(SubscriptionPlan $plan, array $limits): void
    {
        $plan->limits()->delete();
        foreach ($limits as $key => $value) {
            $plan->limits()->create(['key' => $key, 'value' => (string) $value]);
        }
    }

    private function syncPermissionsForPlan(SubscriptionPlan $plan, array $patterns): void
    {
        $plan->planPermissions()->delete();
        foreach ($patterns as $pattern) {
            $plan->planPermissions()->create(['permission_code' => $pattern]);
        }
    }
}
