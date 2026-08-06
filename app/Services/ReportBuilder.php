<?php

namespace App\Services;

use App\Models\ReportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportBuilder
{
    /**
     * Get available tables for report building.
     */
    public static function getAvailableTables(): array
    {
        $tables = [
            'sales_invoices' => [
                'name' => 'Sales Invoices',
                'name_ar' => 'فواتير المبيعات',
                'columns' => self::getColumns('sales_invoices'),
            ],
            'purchase_invoices' => [
                'name' => 'Purchase Invoices',
                'name_ar' => 'فواتير المشتريات',
                'columns' => self::getColumns('purchase_invoices'),
            ],
            'customers' => [
                'name' => 'Customers',
                'name_ar' => 'العملاء',
                'columns' => self::getColumns('customers'),
            ],
            'suppliers' => [
                'name' => 'Suppliers',
                'name_ar' => 'الموردين',
                'columns' => self::getColumns('suppliers'),
            ],
            'items' => [
                'name' => 'Items',
                'name_ar' => 'الأصناف',
                'columns' => self::getColumns('items'),
            ],
            'collections' => [
                'name' => 'Collections',
                'name_ar' => 'التحصيل',
                'columns' => self::getColumns('collections'),
            ],
            'treasuries' => [
                'name' => 'Treasuries',
                'name_ar' => 'الخزائن',
                'columns' => self::getColumns('treasuries'),
            ],
            'employees' => [
                'name' => 'Employees',
                'name_ar' => 'الموظفين',
                'columns' => self::getColumns('employees'),
            ],
        ];

        return $tables;
    }

    /**
     * Get columns for a table.
     */
    public static function getColumns(string $table): array
    {
        $columns = Schema::getColumnListing($table);
        $result = [];
        foreach ($columns as $col) {
            $type = Schema::getColumnType($table, $col);
            $result[] = [
                'name' => $col,
                'type' => $type,
                'label' => self::humanize($col),
            ];
        }
        return $result;
    }

    /**
     * Execute a report definition.
     */
    public static function execute(ReportDefinition $report, ?int $companyId = null, ?int $branchId = null): array
    {
        $table = $report->base_table;
        $query = DB::table($table);

        // Apply company filter if applicable
        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        // Apply branch filter if applicable
        if ($branchId && Schema::hasColumn($table, 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        // Select columns
        $columns = $report->selected_columns ?? ['*'];
        $query->select($columns);

        // Apply filters
        if (!empty($report->filters)) {
            foreach ($report->filters as $filter) {
                $field = $filter['field'] ?? null;
                $operator = $filter['operator'] ?? '=';
                $value = $filter['value'] ?? null;

                if (!$field) continue;

                match ($operator) {
                    '=' => $query->where($field, $value),
                    '!=' => $query->where($field, '!=', $value),
                    '>' => $query->where($field, '>', $value),
                    '>=' => $query->where($field, '>=', $value),
                    '<' => $query->where($field, '<', $value),
                    '<=' => $query->where($field, '<=', $value),
                    'like' => $query->where($field, 'like', "%{$value}%"),
                    'in' => $query->whereIn($field, (array) $value),
                    'not_in' => $query->whereNotIn($field, (array) $value),
                    'between' => $query->whereBetween($field, (array) $value),
                    'null' => $query->whereNull($field),
                    'not_null' => $query->whereNotNull($field),
                    default => null,
                };
            }
        }

        // Apply aggregations
        if (!empty($report->aggregations)) {
            $query->selectRaw(self::buildAggregations($report->aggregations, $report->group_by ?? []));
        }

        // Apply group by
        if (!empty($report->group_by)) {
            $query->groupBy($report->group_by);
        }

        // Apply sort
        if (!empty($report->sort_by)) {
            foreach ($report->sort_by as $sort) {
                $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
            }
        } else {
            $query->latest();
        }

        $results = $query->limit(1000)->get()->toArray();

        // Update run stats
        $report->update([
            'last_run_at' => now(),
            'run_count' => $report->run_count + 1,
        ]);

        return [
            'columns' => $columns,
            'data' => $results,
            'total' => count($results),
        ];
    }

    /**
     * Build SQL aggregations.
     */
    protected static function buildAggregations(array $aggregations, array $groupBy): string
    {
        $parts = [];
        foreach ($aggregations as $agg) {
            $func = strtoupper($agg['function'] ?? 'COUNT');
            $field = $agg['field'] ?? '*';
            $alias = $agg['alias'] ?? strtolower($func) . '_' . str_replace('.', '_', $field);

            $parts[] = "{$func}({$field}) as {$alias}";
        }
        return implode(', ', $parts);
    }

    /**
     * Humanize column name.
     */
    protected static function humanize(string $input): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $input));
    }

    /**
     * Get schema info for a table (for Flutter UI).
     */
    public static function getTableSchema(string $table): array
    {
        if (!Schema::hasTable($table)) {
            return ['error' => 'Table not found'];
        }

        $columns = Schema::getColumns($table);
        $result = [];
        foreach ($columns as $col) {
            $result[] = [
                'name' => $col['name'],
                'type' => $col['type'],
                'nullable' => $col['nullable'],
                'label' => self::humanize($col['name']),
            ];
        }

        return [
            'table' => $table,
            'columns' => $result,
        ];
    }
}
