<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasColumn = DB::select("PRAGMA table_info(rep_daily_settlements)");
        $hasColumn = collect($hasColumn)->contains('name', 'salesman_debt_id');

        if (!$hasColumn) {
            DB::statement('ALTER TABLE rep_daily_settlements ADD COLUMN salesman_debt_id INTEGER REFERENCES salesman_debts(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        // SQLite doesn't support DROP COLUMN easily; skip
    }
};
