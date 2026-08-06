<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $tables = ['sales_invoices', 'payment_vouchers', 'receipt_vouchers'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'posted_at')) {
                        $table->timestamp('posted_at')->nullable()->after('status');
                    }
                });
            }
        }
    }

    public function down(): void {
        $tables = ['sales_invoices', 'payment_vouchers', 'receipt_vouchers'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'posted_at')) {
                        $table->dropColumn('posted_at');
                    }
                });
            }
        }
    }
};
