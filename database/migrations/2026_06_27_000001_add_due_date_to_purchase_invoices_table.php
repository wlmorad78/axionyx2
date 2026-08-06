<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('invoice_date');
            $table->string('purchase_order_no', 50)->nullable()->after('due_date');
            $table->string('invoice_type', 50)->nullable()->after('purchase_order_no')->default('شراء');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'purchase_order_no', 'invoice_type']);
        });
    }
};
