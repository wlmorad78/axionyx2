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
            if (!Schema::hasColumn('sales_invoice_items', 'invoice_date')) {
                $table->date('invoice_date')->nullable();
            }
        });

        DB::statement('
            UPDATE sales_invoice_items
            SET invoice_date = (
                SELECT si.invoice_date
                FROM sales_invoices si
                WHERE si.id = sales_invoice_items.sales_invoice_id
            )
            WHERE invoice_date IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropColumn('invoice_date');
        });
    }
};
