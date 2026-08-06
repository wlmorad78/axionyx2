<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TABLE IF EXISTS rep_daily_settlements_backup');
        DB::statement('CREATE TABLE rep_daily_settlements_backup AS SELECT * FROM rep_daily_settlements');
        DB::statement('DROP TABLE rep_daily_settlements');

        DB::statement('
            CREATE TABLE rep_daily_settlements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL,
                branch_id INTEGER,
                settlement_no VARCHAR(50) UNIQUE NOT NULL,
                settlement_date DATE NOT NULL,
                sales_rep_id INTEGER NOT NULL,
                issue_order_id INTEGER,
                total_sales_value DECIMAL(12,2) DEFAULT 0,
                total_collections_value DECIMAL(12,2) DEFAULT 0,
                total_expenses DECIMAL(12,2) DEFAULT 0,
                total_from_balance DECIMAL(12,2) DEFAULT 0,
                expected_cash DECIMAL(12,2) DEFAULT 0,
                actual_cash DECIMAL(12,2) DEFAULT 0,
                cash_difference DECIMAL(12,2) DEFAULT 0,
                shortage DECIMAL(12,2) DEFAULT 0,
                shortage_status VARCHAR(20) DEFAULT \'pending\',
                notes TEXT,
                status VARCHAR(20) DEFAULT \'draft\',
                created_by INTEGER,
                approved_by INTEGER,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                deleted_at TIMESTAMP,
                CHECK (status IN (\'draft\', \'submitted\', \'approved\', \'cancelled\')),
                CHECK (shortage_status IN (\'pending\', \'paid_next_day\')),
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
                FOREIGN KEY (sales_rep_id) REFERENCES employees(id) ON DELETE CASCADE,
                FOREIGN KEY (issue_order_id) REFERENCES issue_orders(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE SET NULL,
                FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
            )
        ');

        DB::statement('INSERT INTO rep_daily_settlements SELECT * FROM rep_daily_settlements_backup');
        DB::statement('DROP TABLE rep_daily_settlements_backup');
    }

    public function down(): void
    {
        //
    }
};
