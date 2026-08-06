<?php

namespace Database\Seeders;

use App\Models\DashboardWidget;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DashboardWidgetSeeder extends Seeder
{
    private array $widgets = [
        ['code' => 'counters',        'name' => 'Counters',          'name_ar' => 'العدّادات',              'category' => 'general',   'widget_type' => 'counters',  'default_sort_order' => 0,  'default_width' => 3],
        ['code' => 'today_activity',  'name' => 'Today Activity',    'name_ar' => 'نشاط اليوم',             'category' => 'general',   'widget_type' => 'activity',  'default_sort_order' => 1,  'default_width' => 2],
        ['code' => 'month_summary',   'name' => 'Month Summary',     'name_ar' => 'ملخص الشهر',             'category' => 'general',   'widget_type' => 'summary',   'default_sort_order' => 2,  'default_width' => 2],
        ['code' => 'finance_summary', 'name' => 'Finance Summary',   'name_ar' => 'الملخص المالي',          'category' => 'finance',   'widget_type' => 'finance',   'default_sort_order' => 3,  'default_width' => 2],
        ['code' => 'sales_chart',     'name' => 'Sales Chart',       'name_ar' => 'رسم بياني للمبيعات',     'category' => 'sales',     'widget_type' => 'chart',     'default_sort_order' => 4,  'default_width' => 2],
        ['code' => 'recent_sales',    'name' => 'Recent Sales',      'name_ar' => 'آخر فواتير المبيعات',    'category' => 'sales',     'widget_type' => 'list',      'default_sort_order' => 5,  'default_width' => 2],
        ['code' => 'unpaid_invoices', 'name' => 'Unpaid Invoices',   'name_ar' => 'فواتير غير مدفوعة',      'category' => 'finance',   'widget_type' => 'stat',      'default_sort_order' => 6,  'default_width' => 1],
        ['code' => 'top_customers',   'name' => 'Top Customers',     'name_ar' => 'أكبر العملاء',           'category' => 'sales',     'widget_type' => 'list',      'default_sort_order' => 7,  'default_width' => 1],
        ['code' => 'low_stock',       'name' => 'Low Stock Alerts',  'name_ar' => 'تنبيهات نقص المخزون',    'category' => 'inventory', 'widget_type' => 'list',      'default_sort_order' => 8,  'default_width' => 1],
        ['code' => 'purchase_today',  'name' => 'Purchases Today',   'name_ar' => 'مشتريات اليوم',          'category' => 'purchase',  'widget_type' => 'stat',      'default_sort_order' => 9,  'default_width' => 1],
        ['code' => 'employee_count',  'name' => 'Employee Count',    'name_ar' => 'عدد الموظفين',           'category' => 'hr',        'widget_type' => 'stat',      'default_sort_order' => 10, 'default_width' => 1],
        ['code' => 'attendance_today','name' => 'Attendance Today',  'name_ar' => 'حضور اليوم',             'category' => 'hr',        'widget_type' => 'stat',      'default_sort_order' => 11, 'default_width' => 1],
    ];

    public function run(): void
    {
        foreach ($this->widgets as $w) {
            DashboardWidget::updateOrCreate(['code' => $w['code']], $w);
        }

        // Assign default widgets to all roles
        $allWidgetIds = DashboardWidget::pluck('id')->toArray();
        $roles = Role::all();

        foreach ($roles as $role) {
            $syncData = [];
            foreach ($allWidgetIds as $index => $widgetId) {
                $syncData[$widgetId] = [
                    'is_visible' => true,
                    'sort_order' => $index,
                    'width' => DashboardWidget::find($widgetId)->default_width,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $role->widgets()->syncWithoutDetaching($syncData);
        }
    }
}
