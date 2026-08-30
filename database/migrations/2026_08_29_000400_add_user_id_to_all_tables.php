<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function addColumnAndCopy(string $table, string $oldCol, string $newCol): void
    {
        if (!Schema::hasColumn($table, $newCol)) {
            Schema::table($table, function (Blueprint $t) use ($newCol) {
                $t->foreignId($newCol)->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }

        DB::statement("
            UPDATE `$table`
            SET `$newCol` = (
                SELECT e.user_id FROM employees e WHERE e.id = `$table`.`$oldCol`
            )
            WHERE `$oldCol` IS NOT NULL
              AND `$newCol` IS NULL
              AND EXISTS (SELECT 1 FROM employees e WHERE e.id = `$table`.`$oldCol` AND e.user_id IS NOT NULL)
        ");
    }

    private function convertAuditColumn(string $table, string $col): void
    {
        DB::statement("
            UPDATE `$table`
            SET `$col` = (
                SELECT e.user_id FROM employees e WHERE e.id = `$table`.`$col`
            )
            WHERE `$col` IS NOT NULL
              AND EXISTS (SELECT 1 FROM employees e WHERE e.id = `$table`.`$col` AND e.user_id IS NOT NULL)
        ");
    }

    private function convertNamedFk(string $table, string $col): void
    {
        DB::statement("
            UPDATE `$table`
            SET `$col` = (
                SELECT e.user_id FROM employees e WHERE e.id = `$table`.`$col`
            )
            WHERE `$col` IS NOT NULL
              AND EXISTS (SELECT 1 FROM employees e WHERE e.id = `$table`.`$col` AND e.user_id IS NOT NULL)
        ");
    }

    public function up(): void
    {
        // ════════════════════════════════════════════════════════════
        // CATEGORY 1: employee_id → user_id
        // ════════════════════════════════════════════════════════════
        $empTables = [
            'employee_assignments', 'employee_contracts', 'leave_requests',
            'employee_loans', 'employee_advances', 'employee_penalties',
            'employee_rewards', 'employee_shifts', 'attendance_records',
            'employee_missions', 'employee_salary_structures', 'payroll_run_details',
            'salesman_assignments', 'route_schedules', 'customer_visits',
            'load_requests', 'issue_orders', 'return_orders',
            'drivers', 'asset_assignments', 'kpi_targets', 'kpi_results',
            'treasury_custodies', 'rep_item_distributions', 'item_sales_returns',
            'daily_routes', 'routes', 'vehicle_daily_expenses', 'return_order_settlements',
        ];

        foreach ($empTables as $table) {
            if (Schema::hasColumn($table, 'employee_id')) {
                $this->addColumnAndCopy($table, 'employee_id', 'user_id');
            }
        }

        // ════════════════════════════════════════════════════════════
        // CATEGORY 2: supervisor_employee_id → supervisor_user_id
        // ════════════════════════════════════════════════════════════
        if (Schema::hasColumn('load_requests', 'supervisor_employee_id')) {
            $this->addColumnAndCopy('load_requests', 'supervisor_employee_id', 'supervisor_user_id');
        }

        // ════════════════════════════════════════════════════════════
        // CATEGORY 3: manager_employee_id → manager_user_id
        // ════════════════════════════════════════════════════════════
        foreach (['branches', 'warehouses'] as $table) {
            if (Schema::hasColumn($table, 'manager_employee_id')) {
                $this->addColumnAndCopy($table, 'manager_employee_id', 'manager_user_id');
            }
        }

        // ════════════════════════════════════════════════════════════
        // CATEGORY 4: from_employee_id / to_employee_id
        // ════════════════════════════════════════════════════════════
        if (Schema::hasColumn('representative_transfers', 'from_employee_id')) {
            $this->addColumnAndCopy('representative_transfers', 'from_employee_id', 'from_user_id');
        }
        if (Schema::hasColumn('representative_transfers', 'to_employee_id')) {
            $this->addColumnAndCopy('representative_transfers', 'to_employee_id', 'to_user_id');
        }

        // ════════════════════════════════════════════════════════════
        // CATEGORY 5: Convert created_by / approved_by (employee IDs → user IDs)
        // ════════════════════════════════════════════════════════════
        $auditTables = [
            'sales_invoices', 'collections', 'customer_returns', 'salesman_settlements',
            'purchase_requests', 'purchase_orders', 'distribution_plans',
            'return_authorizations', 'salesman_debts', 'rep_daily_settlements',
            'treasury_bank_transfers', 'bank_supplier_payments', 'bank_transfers',
            'issue_orders', 'return_orders', 'customer_agreements',
            'supplier_quotations', 'purchase_receipts', 'purchase_invoices',
            'purchase_returns', 'treasury_transactions', 'owner_transactions',
            'salesman_account_movements', 'salesman_debt_payment_lines',
            'bank_reconciliations', 'load_requests',
            'attendance_adjustments', 'employee_missions', 'treasury_daily_closings',
            'employee_contract_amendments', 'leave_requests',
        ];

        foreach ($auditTables as $table) {
            if (Schema::hasColumn($table, 'created_by')) {
                $this->convertAuditColumn($table, 'created_by');
            }
            if (Schema::hasColumn($table, 'approved_by')) {
                $this->convertAuditColumn($table, 'approved_by');
            }
        }

        // load_requests has 'create_by' (typo column)
        if (Schema::hasColumn('load_requests', 'create_by')) {
            $this->convertNamedFk('load_requests', 'create_by');
        }

        // ════════════════════════════════════════════════════════════
        // CATEGORY 6: sales_rep_id / salesman_id
        // ════════════════════════════════════════════════════════════
        $salesRepTables = [
            'sales_invoices', 'collections', 'customer_returns',
            'salesman_settlements', 'daily_distribution_dashboards',
            'route_visits', 'vehicle_assignments', 'sales_targets',
            'gps_tracking_sessions', 'sync_batches', 'mobile_devices',
            'merchandising_visits', 'shelf_share_surveys', 'competitor_price_surveys',
            'competitor_photos', 'market_issues', 'survey_responses',
            'survey_assignments', 'merchandising_audits', 'merchandising_task_assignments',
            'vehicle_settlements', 'distribution_plan_reps', 'devices',
            'rep_daily_settlements',
        ];

        foreach ($salesRepTables as $table) {
            if (Schema::hasColumn($table, 'sales_rep_id')) {
                $this->convertNamedFk($table, 'sales_rep_id');
            }
        }

        foreach (['return_authorizations', 'salesman_account_movements', 'salesman_accounts', 'salesman_debts', 'salesman_debt_payment_lines'] as $table) {
            if (Schema::hasColumn($table, 'salesman_id')) {
                $this->convertNamedFk($table, 'salesman_id');
            }
        }

        // ════════════════════════════════════════════════════════════
        // CATEGORY 7: Other named FKs
        // ════════════════════════════════════════════════════════════
        $namedFks = [
            ['collections', 'collected_from_id'],
            ['purchase_requests', 'requested_by'],
            ['load_requests', 'requested_by'],
            ['issue_orders', 'issued_by'],
            ['issue_orders', 'received_by'],
            ['return_orders', 'received_by'],
            ['treasury_shifts', 'cashier_id'],
            ['treasury_counts', 'counted_by'],
            ['route_assignments', 'driver_id'],
            ['route_assignments', 'assistant_id'],
            ['competitor_new_products', 'reported_by'],
            ['employee_assignments', 'direct_manager_id'],
            ['salesman_debt_payment_lines', 'received_by'],
        ];

        foreach ($namedFks as [$table, $col]) {
            if (Schema::hasColumn($table, $col)) {
                $this->convertNamedFk($table, $col);
            }
        }
    }

    public function down(): void
    {
        // لا يمكن التراجع — يُفضل نسخة احتياطية
    }
};
