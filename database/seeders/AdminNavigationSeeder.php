<?php

namespace Database\Seeders;

use App\Models\AdminModule;
use App\Models\AdminScreen;
use App\Models\Role;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;

class AdminNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // 🏠 الرئيسية
            [
                'key' => 'home',
                'title' => 'الرئيسية',
                'icon' => 'dashboard',
                'sort_order' => 0,
                'screens' => [
                    ['key' => 'dashboard', 'title' => 'لوحة التحكم', 'icon' => 'dashboard', 'route' => '/admin/dashboard', 'screen_type' => 'dashboard', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT, RoleNames::SALES_REP, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'notifications', 'title' => 'الإشعارات', 'icon' => 'notifications', 'route' => '/admin/notifications', 'api_resource' => 'notifications', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT, RoleNames::SALES_REP, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'daily-tasks', 'title' => 'المهام اليومية', 'icon' => 'task_alt', 'route' => '/admin/daily-tasks', 'api_resource' => 'daily-tasks', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT, RoleNames::SALES_REP, RoleNames::WAREHOUSE_KEEPER]],
                ],
            ],
            // 👤 إدارة النظام
            [
                'key' => 'system-admin',
                'title' => 'إدارة النظام',
                'icon' => 'admin_panel_settings',
                'sort_order' => 10,
                'screens' => [
                    ['key' => 'users', 'title' => 'المستخدمون', 'icon' => 'people', 'route' => '/admin/users', 'api_resource' => 'users', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'roles', 'title' => 'الأدوار والصلاحيات', 'icon' => 'admin_panel_settings', 'route' => '/admin/roles', 'api_resource' => 'roles', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'user-roles', 'title' => 'ربط المستخدمين بالأدوار', 'icon' => 'link', 'route' => '/admin/user-roles', 'api_resource' => 'user-roles', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'activity-logs', 'title' => 'سجل النشاط', 'icon' => 'history', 'route' => '/admin/activity-logs', 'api_resource' => 'activity-logs', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'settings', 'title' => 'إعدادات النظام', 'icon' => 'settings', 'route' => '/admin/settings', 'screen_type' => 'settings', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT, RoleNames::SALES_REP, RoleNames::WAREHOUSE_KEEPER]],
                ],
            ],
            // 👨‍💼 الموارد البشرية (HR)
            [
                'key' => 'hr',
                'title' => 'الموارد البشرية',
                'icon' => 'badge',
                'sort_order' => 15,
                'screens' => [
                    ['key' => 'departments', 'title' => 'الإدارات', 'icon' => 'apartment', 'route' => '/admin/departments', 'api_resource' => 'departments', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'position-levels', 'title' => 'المستويات الوظيفية', 'icon' => 'stacked_line_chart', 'route' => '/admin/position-levels', 'api_resource' => 'position-levels', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'job-positions', 'title' => 'الوظائف', 'icon' => 'work', 'route' => '/admin/job-positions', 'api_resource' => 'job-positions', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'org-structure', 'title' => 'الهيكل التنظيمي', 'icon' => 'account_tree', 'route' => '/admin/org-structure', 'api_resource' => 'org-structures', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'employees', 'title' => 'الموظفون', 'icon' => 'badge', 'route' => '/admin/employees', 'api_resource' => 'employees', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'attendance', 'title' => 'الحضور والانصراف', 'icon' => 'access_time', 'route' => '/admin/attendance', 'api_resource' => 'attendance-records', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'leaves', 'title' => 'الإجازات', 'icon' => 'beach_access', 'route' => '/admin/leaves', 'api_resource' => 'leave-requests', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'payroll', 'title' => 'الرواتب', 'icon' => 'payments', 'route' => '/admin/payroll', 'api_resource' => 'payroll-runs', 'roles' => [RoleNames::ADMIN]],
                ],
            ],
            // 🚚 إدارة التوزيع والمندوبين
            [
                'key' => 'distribution',
                'title' => 'إدارة التوزيع والمندوبين',
                'icon' => 'local_shipping',
                'sort_order' => 20,
                'screens' => [
                    ['key' => 'representatives', 'title' => 'المندوبون', 'icon' => 'delivery_dining', 'route' => '/admin/representatives', 'api_resource' => 'representatives', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER, RoleNames::SALES_REP]],
                    ['key' => 'sales-territories', 'title' => 'مناطق المندوبين', 'icon' => 'map', 'route' => '/admin/sales-territories', 'api_resource' => 'sales-territories', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'route-plans', 'title' => 'خط السير', 'icon' => 'route', 'route' => '/admin/route-plans', 'api_resource' => 'route-plans', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'visits', 'title' => 'زيارات العملاء', 'icon' => 'handshake', 'route' => '/admin/visits', 'api_resource' => 'visits', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'rep-tracking', 'title' => 'تتبع المندوبين', 'icon' => 'location_on', 'route' => '/admin/rep-tracking', 'api_resource' => 'rep-tracking', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER, RoleNames::SALES_REP]],
                    ['key' => 'rep-area-assignments', 'title' => 'ربط المندوبين بالمناطق', 'icon' => 'layers', 'route' => '/admin/rep-area-assignments', 'api_resource' => 'rep-area-assignments', 'roles' => [RoleNames::ADMIN]],
                ],
            ],
            // 👥 العملاء
            [
                'key' => 'customers',
                'title' => 'العملاء',
                'icon' => 'groups',
                'sort_order' => 25,
                'screens' => [
                    ['key' => 'customers', 'title' => 'العملاء', 'icon' => 'store', 'route' => '/admin/customers', 'api_resource' => 'customers', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP, RoleNames::ACCOUNTANT]],
                    ['key' => 'customer-categories', 'title' => 'تصنيفات العملاء', 'icon' => 'category', 'route' => '/admin/customer-categories', 'api_resource' => 'customer-categories', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'price-categories', 'title' => 'فئات الأسعار', 'icon' => 'price_change', 'route' => '/admin/price-categories', 'api_resource' => 'price-categories', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'contacts', 'title' => 'جهات الاتصال', 'icon' => 'contact_phone', 'route' => '/admin/contacts', 'api_resource' => 'contacts', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'customer-debts', 'title' => 'مديونيات العملاء', 'icon' => 'account_balance_wallet', 'route' => '/admin/customer-debts', 'api_resource' => 'customer-debts', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP, RoleNames::ACCOUNTANT]],
                ],
            ],
            // 📦 المخزون
            [
                'key' => 'inventory',
                'title' => 'المخزون',
                'icon' => 'inventory_2',
                'sort_order' => 30,
                'screens' => [
                    ['key' => 'products', 'title' => 'المنتجات', 'icon' => 'inventory_2', 'route' => '/admin/products', 'api_resource' => 'products', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER, RoleNames::SALES_REP, RoleNames::ACCOUNTANT]],
                    ['key' => 'categories', 'title' => 'مجموعات المنتجات', 'icon' => 'folder', 'route' => '/admin/categories', 'api_resource' => 'categories', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'sub-categories', 'title' => 'التصنيفات الفرعية', 'icon' => 'category', 'route' => '/admin/sub-categories', 'api_resource' => 'sub-categories', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'units', 'title' => 'الوحدات', 'icon' => 'straighten', 'route' => '/admin/units', 'api_resource' => 'units', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'warehouses', 'title' => 'المخازن', 'icon' => 'warehouse', 'route' => '/admin/warehouses', 'api_resource' => 'warehouses', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'product-stocks', 'title' => 'أرصدة المخزون', 'icon' => 'bar_chart', 'route' => '/admin/product-stocks', 'api_resource' => 'product-stocks', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'inventory-movements', 'title' => 'حركات المخزون', 'icon' => 'sync_alt', 'route' => '/admin/inventory-movements', 'api_resource' => 'inventory-movements', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'inventory-count', 'title' => 'الجرد', 'icon' => 'fact_check', 'route' => '/admin/inventory-count', 'api_resource' => 'inventory-counts', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                ],
            ],
            // 🛒 المبيعات
            [
                'key' => 'sales',
                'title' => 'المبيعات',
                'icon' => 'point_of_sale',
                'sort_order' => 35,
                'screens' => [
                    ['key' => 'price-quotes', 'title' => 'عروض الأسعار', 'icon' => 'request_quote', 'route' => '/admin/price-quotes', 'api_resource' => 'price-quotes', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'sales-orders', 'title' => 'أوامر البيع', 'icon' => 'shopping_cart_checkout', 'route' => '/admin/sales-orders', 'api_resource' => 'sales-orders', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'invoices', 'title' => 'فواتير البيع', 'icon' => 'receipt_long', 'route' => '/admin/invoices', 'api_resource' => 'invoices', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP, RoleNames::ACCOUNTANT]],
                    ['key' => 'sales-returns', 'title' => 'مرتجعات البيع', 'icon' => 'assignment_return', 'route' => '/admin/sales-returns', 'api_resource' => 'sales-returns', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP]],
                    ['key' => 'customer-payments', 'title' => 'تحصيلات العملاء', 'icon' => 'payments', 'route' => '/admin/customer-payments', 'api_resource' => 'customer-payments', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP, RoleNames::ACCOUNTANT]],
                ],
            ],
            // 🚛 المشتريات
            [
                'key' => 'purchases',
                'title' => 'المشتريات',
                'icon' => 'shopping_cart',
                'sort_order' => 40,
                'screens' => [
                    ['key' => 'suppliers', 'title' => 'الموردون', 'icon' => 'factory', 'route' => '/admin/suppliers', 'api_resource' => 'suppliers', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'supplier-offers', 'title' => 'عروض الموردين', 'icon' => 'description', 'route' => '/admin/supplier-offers', 'api_resource' => 'supplier-offers', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'purchase-orders', 'title' => 'أوامر الشراء', 'icon' => 'shopping_bag', 'route' => '/admin/purchase-orders', 'api_resource' => 'purchase-orders', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'purchases', 'title' => 'فواتير الشراء', 'icon' => 'receipt', 'route' => '/admin/purchases', 'api_resource' => 'purchases', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'purchase-returns', 'title' => 'مرتجعات الشراء', 'icon' => 'undo', 'route' => '/admin/purchase-returns', 'api_resource' => 'purchase-returns', 'roles' => [RoleNames::ADMIN]],
                ],
            ],
            // 💰 المالية
            [
                'key' => 'finance',
                'title' => 'المالية',
                'icon' => 'account_balance',
                'sort_order' => 45,
                'screens' => [
                    ['key' => 'chart-of-accounts', 'title' => 'دليل الحسابات', 'icon' => 'account_tree', 'route' => '/admin/chart-of-accounts', 'api_resource' => 'chart-of-accounts', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'cashboxes', 'title' => 'الصندوق', 'icon' => 'point_of_sale', 'route' => '/admin/cashboxes', 'api_resource' => 'cashboxes', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'banks', 'title' => 'البنوك', 'icon' => 'account_balance', 'route' => '/admin/banks', 'api_resource' => 'banks', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'bank-accounts', 'title' => 'حسابات البنوك', 'icon' => 'account_balance', 'route' => '/admin/bank-accounts', 'api_resource' => 'bank-accounts', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'bank-transfers', 'title' => 'التحويلات البنكية', 'icon' => 'swap_horiz', 'route' => '/admin/bank-transfers', 'api_resource' => 'bank-transfers', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'bank-reconciliations', 'title' => 'التسويات البنكية', 'icon' => 'fact_check', 'route' => '/admin/bank-reconciliations', 'api_resource' => 'bank-reconciliations', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'cash-transactions', 'title' => 'حركات الصناديق', 'icon' => 'payments', 'route' => '/admin/cash-transactions', 'api_resource' => 'cash-transactions', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'bank-transactions', 'title' => 'حركات البنوك', 'icon' => 'currency_exchange', 'route' => '/admin/bank-transactions', 'api_resource' => 'bank-transactions', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'receipt-vouchers', 'title' => 'سند قبض', 'icon' => 'download', 'route' => '/admin/receipt-vouchers', 'api_resource' => 'receipt-vouchers', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'payment-vouchers', 'title' => 'سند صرف', 'icon' => 'upload', 'route' => '/admin/payment-vouchers', 'api_resource' => 'payment-vouchers', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'journal-entries', 'title' => 'القيود اليومية', 'icon' => 'menu_book', 'route' => '/admin/journal-entries', 'api_resource' => 'journal-entries', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                    ['key' => 'trial-balance', 'title' => 'ميزان المراجعة', 'icon' => 'balance', 'route' => '/admin/trial-balance', 'api_resource' => 'trial-balance', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                ],
            ],
            // ⚙️ الإعدادات العامة
            [
                'key' => 'general-settings',
                'title' => 'الإعدادات العامة',
                'icon' => 'settings',
                'sort_order' => 50,
                'screens' => [
                    ['key' => 'company-settings', 'title' => 'بيانات الشركة', 'icon' => 'business', 'route' => '/admin/company-settings', 'api_resource' => 'company-settings', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'governorates', 'title' => 'المحافظات', 'icon' => 'public', 'route' => '/admin/governorates', 'api_resource' => 'governorates', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'cities', 'title' => 'المدن', 'icon' => 'location_city', 'route' => '/admin/cities', 'api_resource' => 'cities', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'districts', 'title' => 'الأحياء', 'icon' => 'place', 'route' => '/admin/districts', 'api_resource' => 'districts', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'areas', 'title' => 'المناطق', 'icon' => 'map', 'route' => '/admin/areas', 'api_resource' => 'areas', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'streets', 'title' => 'الشوارع', 'icon' => 'route', 'route' => '/admin/streets', 'api_resource' => 'streets', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'tax-types', 'title' => 'أنواع الضرائب', 'icon' => 'percent', 'route' => '/admin/tax-types', 'api_resource' => 'tax-types', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'currencies', 'title' => 'العملات', 'icon' => 'currency_exchange', 'route' => '/admin/currencies', 'api_resource' => 'currencies', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'fiscal-years', 'title' => 'سنوات العمل', 'icon' => 'calendar_month', 'route' => '/admin/fiscal-years', 'api_resource' => 'fiscal-years', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                ],
            ],
            // 📦 إدارة الباقات والاشتراكات
            [
                'key' => 'subscription',
                'title' => 'إدارة الباقات',
                'icon' => 'card_membership',
                'sort_order' => 12,
                'screens' => [
                    ['key' => 'subscription-plans', 'title' => 'باقات الاشتراك', 'icon' => 'view_list', 'route' => '/subscription-plans', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                    ['key' => 'company-subscriptions', 'title' => 'اشتراكات الشركات', 'icon' => 'business_center', 'route' => '/admin/company-subscriptions', 'api_resource' => 'company-subscriptions', 'screen_type' => 'resource', 'roles' => [RoleNames::ADMIN]],
                ],
            ],
            // 📈 التقارير
            [
                'key' => 'reports',
                'title' => 'التقارير',
                'icon' => 'assessment',
                'sort_order' => 55,
                'screens' => [
                    ['key' => 'sales-reports', 'title' => 'تقارير المبيعات', 'icon' => 'trending_up', 'route' => '/admin/sales-reports', 'screen_type' => 'reports', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT, RoleNames::SALES_REP, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'purchase-reports', 'title' => 'تقارير المشتريات', 'icon' => 'shopping_bag', 'route' => '/admin/purchase-reports', 'screen_type' => 'reports', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'inventory-reports', 'title' => 'تقارير المخزون', 'icon' => 'inventory', 'route' => '/admin/inventory-reports', 'screen_type' => 'reports', 'roles' => [RoleNames::ADMIN, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'customer-reports', 'title' => 'تقارير العملاء', 'icon' => 'people_alt', 'route' => '/admin/customer-reports', 'screen_type' => 'reports', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP, RoleNames::ACCOUNTANT]],
                    ['key' => 'rep-reports', 'title' => 'تقارير المندوبين', 'icon' => 'local_shipping', 'route' => '/admin/rep-reports', 'screen_type' => 'reports', 'roles' => [RoleNames::ADMIN, RoleNames::SALES_REP, RoleNames::WAREHOUSE_KEEPER]],
                    ['key' => 'financial-reports', 'title' => 'التقارير المالية', 'icon' => 'assessment', 'route' => '/admin/financial-reports', 'screen_type' => 'reports', 'roles' => [RoleNames::ADMIN, RoleNames::ACCOUNTANT]],
                ],
            ],
        ];

        foreach ($modules as $index => $moduleData) {
            $screens = $moduleData['screens'];
            unset($moduleData['screens']);

            $module = AdminModule::updateOrCreate(
                ['key' => $moduleData['key']],
                $moduleData
            );

            foreach ($screens as $sortOrder => $screenData) {
                $roleNames = $screenData['roles'];
                unset($screenData['roles']);

                $screen = AdminScreen::updateOrCreate(
                    ['key' => $screenData['key']],
                    array_merge($screenData, [
                        'module_id' => $module->id,
                        'sort_order' => $sortOrder,
                    ])
                );

                $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
                $screen->roles()->sync($roleIds);
            }
        }
    }
}
