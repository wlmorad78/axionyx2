<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasColumn = DB::select("PRAGMA table_info(collections)");
        $customerCol = collect($hasColumn)->firstWhere('name', 'customer_id');
        if ($customerCol && $customerCol->notnull) {
            DB::statement('CREATE TABLE collections_backup AS SELECT * FROM collections');
            DB::statement('DROP TABLE collections');
            DB::statement("
                CREATE TABLE collections (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    company_id INTEGER NOT NULL,
                    branch_id INTEGER,
                    collection_no VARCHAR(50) UNIQUE NOT NULL,
                    collection_date DATE NOT NULL,
                    collection_time TIME,
                    sales_rep_id INTEGER,
                    customer_id INTEGER,
                    sales_invoice_id INTEGER,
                    payment_method_id INTEGER,
                    safe_id UNSIGNED BIGINT,
                    bank_account_id UNSIGNED BIGINT,
                    amount DECIMAL(12,2) DEFAULT 0,
                    reference_no VARCHAR(100),
                    collection_type VARCHAR(50),
                    debt_id INTEGER,
                    debt_payment_line_id INTEGER,
                    collected_from_id INTEGER,
                    notes TEXT,
                    status VARCHAR(20) DEFAULT 'draft',
                    created_by INTEGER,
                    approved_by INTEGER,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    deleted_at TIMESTAMP,
                    FOREIGN KEY (company_id) REFERENCES companies(id),
                    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
                    FOREIGN KEY (sales_rep_id) REFERENCES employees(id) ON DELETE SET NULL,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
                    FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE SET NULL
                )
            ");
            DB::statement('INSERT INTO collections SELECT * FROM collections_backup');
            DB::statement('DROP TABLE collections_backup');
        }
    }

    public function down(): void
    {
        //
    }
};
