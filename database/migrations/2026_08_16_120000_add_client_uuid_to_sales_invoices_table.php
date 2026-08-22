<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add client_uuid: the stable offline identifier the Flutter app assigns to an
     * invoice (e.g. "SALE-1786898911325-7076"). It is the sync key together with
     * company_id (NOT invoice_no, which is a server-generated business number).
     *
     * Nullable so pre-existing invoices (which have no client_uuid) keep working.
     * UNIQUE(company_id, client_uuid) prevents the same offline invoice from being
     * created twice within a company. Multiple NULLs are allowed by SQLite/MySQL.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('sales_invoices', 'client_uuid')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->string('client_uuid', 100)->nullable()->after('uuid');
                $table->unique(['company_id', 'client_uuid'], 'sales_invoices_company_client_uuid_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_invoices', 'client_uuid')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropUnique('sales_invoices_company_client_uuid_unique');
                $table->dropColumn('client_uuid');
            });
        }
    }
};
