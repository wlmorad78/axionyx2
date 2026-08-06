<?php

namespace Database\Seeders;

use App\Models\ReportDefinition;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'code' => 'sales-summary',
                'name' => 'Sales Summary',
                'name_ar' => 'ملخص المبيعات',
                'category' => 'sales',
                'base_table' => 'sales_invoices',
                'selected_columns' => ['id', 'invoice_number', 'customer_id', 'invoice_date', 'total', 'discount', 'net_amount', 'status'],
                'filters' => null,
                'sort_by' => [['field' => 'invoice_date', 'direction' => 'desc']],
                'group_by' => null,
                'aggregations' => [
                    ['function' => 'count', 'field' => '*', 'alias' => 'total_invoices'],
                    ['function' => 'sum', 'field' => 'total', 'alias' => 'total_amount'],
                    ['function' => 'sum', 'field' => 'net_amount', 'alias' => 'total_net'],
                ],
                'chart_config' => ['type' => 'bar', 'x' => 'invoice_date', 'y' => 'net_amount'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'top-customers',
                'name' => 'Top Customers',
                'name_ar' => 'أكبر العملاء',
                'category' => 'sales',
                'base_table' => 'sales_invoices',
                'selected_columns' => ['customer_id'],
                'filters' => null,
                'sort_by' => [['field' => 'total', 'direction' => 'desc']],
                'group_by' => ['customer_id'],
                'aggregations' => [
                    ['function' => 'count', 'field' => '*', 'alias' => 'invoice_count'],
                    ['function' => 'sum', 'field' => 'total', 'alias' => 'total_purchases'],
                ],
                'chart_config' => ['type' => 'pie', 'x' => 'customer_id', 'y' => 'total_purchases'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'purchases-summary',
                'name' => 'Purchases Summary',
                'name_ar' => 'ملخص المشتريات',
                'category' => 'purchases',
                'base_table' => 'purchase_invoices',
                'selected_columns' => ['id', 'invoice_number', 'supplier_id', 'invoice_date', 'total', 'discount', 'net_amount', 'status'],
                'filters' => null,
                'sort_by' => [['field' => 'invoice_date', 'direction' => 'desc']],
                'group_by' => null,
                'aggregations' => [
                    ['function' => 'count', 'field' => '*', 'alias' => 'total_invoices'],
                    ['function' => 'sum', 'field' => 'total', 'alias' => 'total_amount'],
                ],
                'chart_config' => ['type' => 'line', 'x' => 'invoice_date', 'y' => 'total_amount'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'inventory-stock-levels',
                'name' => 'Inventory Stock Levels',
                'name_ar' => 'مستويات المخزون',
                'category' => 'inventory',
                'base_table' => 'items',
                'selected_columns' => ['id', 'name', 'code', 'current_stock', 'minimum_stock', 'unit'],
                'filters' => null,
                'sort_by' => [['field' => 'current_stock', 'direction' => 'asc']],
                'group_by' => null,
                'aggregations' => null,
                'chart_config' => ['type' => 'bar', 'x' => 'name', 'y' => 'current_stock'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'collection-report',
                'name' => 'Collection Report',
                'name_ar' => 'تقرير التحصيل',
                'category' => 'treasury',
                'base_table' => 'collections',
                'selected_columns' => ['id', 'customer_id', 'amount', 'collection_date', 'treasury_id', 'payment_method'],
                'filters' => null,
                'sort_by' => [['field' => 'collection_date', 'direction' => 'desc']],
                'group_by' => null,
                'aggregations' => [
                    ['function' => 'count', 'field' => '*', 'alias' => 'total_collections'],
                    ['function' => 'sum', 'field' => 'amount', 'alias' => 'total_collected'],
                ],
                'chart_config' => ['type' => 'line', 'x' => 'collection_date', 'y' => 'total_collected'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'employee-summary',
                'name' => 'Employee Summary',
                'name_ar' => 'ملخص الموظفين',
                'category' => 'hr',
                'base_table' => 'employees',
                'selected_columns' => ['id', 'first_name', 'last_name', 'department_id', 'position', 'status'],
                'filters' => null,
                'sort_by' => null,
                'group_by' => ['department_id'],
                'aggregations' => [
                    ['function' => 'count', 'field' => '*', 'alias' => 'employee_count'],
                ],
                'chart_config' => ['type' => 'pie', 'x' => 'department_id', 'y' => 'employee_count'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'profit-loss',
                'name' => 'Profit & Loss',
                'name_ar' => 'الأرباح والخسائر',
                'category' => 'reports',
                'base_table' => 'sales_invoices',
                'selected_columns' => ['invoice_date'],
                'filters' => null,
                'sort_by' => [['field' => 'invoice_date', 'direction' => 'desc']],
                'group_by' => ['invoice_date'],
                'aggregations' => [
                    ['function' => 'sum', 'field' => 'total', 'alias' => 'total_revenue'],
                    ['function' => 'sum', 'field' => 'discount', 'alias' => 'total_discounts'],
                    ['function' => 'sum', 'field' => 'net_amount', 'alias' => 'net_revenue'],
                ],
                'chart_config' => ['type' => 'bar', 'x' => 'invoice_date', 'y' => 'net_revenue'],
                'is_template' => true,
                'is_public' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($templates as $data) {
            ReportDefinition::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
