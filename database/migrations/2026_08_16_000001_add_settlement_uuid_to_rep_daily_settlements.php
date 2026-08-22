<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rep_daily_settlements', function (Blueprint $table) {
            $table->string('settlement_uuid', 100)->nullable()->after('settlement_no');
            $table->unique(['company_id', 'sales_rep_id', 'settlement_uuid']);
        });
    }

    public function down(): void
    {
        Schema::table('rep_daily_settlements', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'sales_rep_id', 'settlement_uuid']);
            $table->dropColumn('settlement_uuid');
        });
    }
};
