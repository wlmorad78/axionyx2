<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->decimal('profit', 15, 2)->after('total_cost')->default(0);
        });

        DB::statement('UPDATE sales_invoice_items SET unit_cost = 47.85');
        DB::statement('UPDATE sales_invoice_items SET total_cost = qty * unit_cost');
        DB::statement('UPDATE sales_invoice_items SET profit = net_amount - total_cost');
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropColumn('profit');
        });
    }
};
