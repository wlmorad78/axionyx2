<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE sales_invoice_items
            SET profit = ROUND(COALESCE(net_amount, 0) - COALESCE(total_cost, 0), 2)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('UPDATE sales_invoice_items SET profit = 0');
    }
};
