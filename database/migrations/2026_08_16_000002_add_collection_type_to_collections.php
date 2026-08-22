<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            if (!Schema::hasColumn('collections', 'collection_type')) {
                $table->string('collection_type', 50)->nullable()->after('collection_no');
            }
            $table->index(['company_id', 'sales_rep_id', 'collection_date', 'collection_type'], 'collections_settlement_lookup');
        });

        // Backfill existing data
        DB::table('collections')
            ->where('notes', 'like', '%الدفع من رصيد سابق%')
            ->whereNull('collection_type')
            ->update(['collection_type' => 'balance_payment']);

        DB::table('collections')
            ->whereNull('collection_type')
            ->update(['collection_type' => 'cash']);
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('collections_settlement_lookup');
            $table->dropColumn('collection_type');
        });
    }
};
