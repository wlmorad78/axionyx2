<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // item_categories: drop global unique, add composite unique on [company_id, code]
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });

        // item_sub_categories: add company_id, drop global unique, add composite unique
        Schema::table('item_sub_categories', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies');
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });

        // price_lists: drop global unique, add composite unique on [company_id, code]
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['code']);
        });

        Schema::table('item_sub_categories', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['code']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['code']);
        });
    }
};
