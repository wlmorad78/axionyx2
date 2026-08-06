<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClearDataController extends Controller
{
    private array $groups = [
        'sales' => [
            'label' => 'المبيعات',
            'tables' => [
                'sales_invoices' => 'فواتير المبيعات',
                'sales_invoice_items' => 'أصناف فواتير المبيعات',
                'sales_invoice_discounts' => 'خصومات الفواتير',
                'sales_invoice_taxes' => 'ضرائب الفواتير',
                'sales_invoice_incentives' => 'حوافز المبيعات',
                'collections' => 'التحصيلات',
                'customer_returns' => 'مرتجعات العملاء',
                'e_invoice_transactions' => 'الفواتير الإلكترونية',
                'promotion_execution_logs' => 'سجلات التنفيذ الترويجي',
            ],
        ],
        'purchase' => [
            'label' => 'المشتريات',
            'tables' => [
                'purchase_invoices' => 'فواتير المشتريات',
                'purchase_invoice_items' => 'أصناف فواتير المشتريات',
                'purchase_returns' => 'مرتجعات المشتريات',
                'purchase_expenses' => 'مصاريف المشتريات',
            ],
        ],
        'distribution' => [
            'label' => 'التوزيع',
            'tables' => [
                'distribution_plans' => 'خطط التوزيع',
                'distribution_plan_items' => 'أصناف خطط التوزيع',
                'distribution_plan_customers' => 'عملاء خطط التوزيع',
                'distribution_plan_reps' => 'مندوبي خطط التوزيع',
                'distribution_plan_products' => 'منتجات خطط التوزيع',
            ],
        ],
        'loading' => [
            'label' => 'التحميل والصرف',
            'tables' => [
                'load_requests' => 'طلبات التحميل',
                'load_request_items' => 'أصناف طلبات التحميل',
                'issue_orders' => 'أوامر الصرف',
                'vehicle_loads' => 'التحميلات',
                'vehicle_load_items' => 'أصناف التحميلات',
                'vehicle_loadings' => 'عمليات التحميل',
                'return_orders' => 'أوامر الارتجاع',
                'salesman_settlements' => 'تسوية مناديب البيع',
            ],
        ],
        'treasury' => [
            'label' => 'الخزنة',
            'tables' => [
                'treasury_shifts' => 'ورديات الخزنة',
                'treasury_shift_transactions' => 'معاملات الورديات',
                'treasury_transfers' => 'تحويلات الخزنة',
                'treasury_adjustments' => 'تعديلات الخزنة',
                'treasury_daily_closings' => 'الإقفالات اليومية',
                'treasury_count_details' => 'تفاصيل عد الخزنة',
                'treasury_counts' => 'عمليات العد',
                'receipt_vouchers' => 'سندات القبض',
                'payment_vouchers' => 'سندات الصرف',
            ],
        ],
        'accounting' => [
            'label' => 'الحسابات',
            'tables' => [
                'journal_entries' => 'القيود اليومية',
                'journal_entry_lines' => 'أطراف القيود اليومية',
                'manual_journal_entries' => 'القيود اليدوية',
                'manual_journal_entry_lines' => 'أطراف القيود اليدوية',
            ],
        ],
        'hr' => [
            'label' => 'الموارد البشرية',
            'tables' => [
                'attendance_records' => 'سجلات الحضور',
                'attendance_adjustments' => 'تعديلات الحضور',
                'leave_requests' => 'طلبات الإجازات',
                'employee_loans' => 'قروض الموظفين',
                'employee_advances' => 'سلف الموظفين',
                'employee_penalties' => 'جزاءات الموظفين',
                'employee_rewards' => 'مكافآت الموظفين',
                'payroll_run_details' => 'تفاصيل كشوف الرواتب',
                'payroll_runs' => 'كشوف الرواتب',
                'employee_missions' => 'مهام الموظفين',
            ],
        ],
        'fleet' => [
            'label' => 'الأسطول',
            'tables' => [
                'vehicle_fuel_transactions' => 'معاملات الوقود',
                'vehicle_maintenance' => 'صيانة المركبات',
                'vehicle_expenses' => 'مصاريف المركبات',
                'vehicle_accidents' => 'حوادث المركبات',
                'vehicle_trip_history' => 'سجل الرحلات',
                'vehicle_speed_violations' => 'مخالفات السرعة',
                'vehicle_idle_time' => 'وقت التوقف',
            ],
        ],
        'inventory' => [
            'label' => 'المخزون',
            'tables' => [
                'inventory_transactions' => 'معاملات المخزون',
                'inventory_transaction_items' => 'أصناف معاملات المخزون',
                'stock_adjustments' => 'تعديلات المخزون',
                'stock_adjustment_items' => 'أصناف تعديلات المخزون',
                'stock_counts' => 'عمليات الجرد',
                'stock_count_items' => 'أصناف الجرد',
                'warehouse_transfers' => 'تحويلات المستودعات',
                'warehouse_transfer_items' => 'أصناف التحويلات',
            ],
        ],
        'crm' => [
            'label' => 'إدارة العلاقات',
            'tables' => [
                'leads' => 'العملاء المحتملون',
                'lead_activities' => 'أنشطة العملاء المحتملين',
                'opportunities' => 'الفرص',
                'customer_visits' => 'زيارات العملاء',
            ],
        ],
    ];

    public function index()
    {
        $groups = $this->groups;
        $counts = [];

        foreach ($groups as $key => $group) {
            foreach ($group['tables'] as $table => $label) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $counts[$table] = DB::table($table)->count();
                } else {
                    $counts[$table] = 0;
                }
            }
        }

        return view('admin.clear-data', compact('groups', 'counts'));
    }

    public function clearGroup(string $group)
    {
        if (!isset($this->groups[$group])) {
            return back()->with('error', 'مجموعة غير موجودة');
        }

        $tables = $this->groups[$group]['tables'];
        $label = $this->groups[$group]['label'];

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $total = 0;
        foreach ($tables as $table => $tableLabel) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $total += $count;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return back()->with('success', "تم مسح {$label} بنجاح ({$total} سجل)");
    }

    public function clearTable(string $table)
    {
        $found = false;
        $label = $table;

        foreach ($this->groups as $group) {
            if (isset($group['tables'][$table])) {
                $found = true;
                $label = $group['tables'][$table];
                break;
            }
        }

        if (!$found) {
            return back()->with('error', 'جدول غير موجود');
        }

        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return back()->with('error', "الجدول {$table} غير موجود في قاعدة البيانات");
        }

        $count = DB::table($table)->count();

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return back()->with('success', "تم مسح {$label} بنجاح ({$count} سجل)");
    }

    public function clearAll()
    {
        $allTables = [];
        foreach ($this->groups as $group) {
            $allTables = array_merge($allTables, array_keys($group['tables']));
        }
        $allTables = array_unique($allTables);

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $total = 0;
        foreach ($allTables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $total += $count;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return back()->with('success', "تم مسح جميع البيانات بنجاح ({$total} سجل)");
    }
}
