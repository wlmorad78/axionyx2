<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_types')
            ->whereNull('name_ar')
            ->where('name_ar', '')
            ->update(['name_ar' => DB::raw('NULL')]);

        DB::table('user_types')
            ->whereNull('name_en')
            ->where('name_en', '')
            ->update(['name_en' => DB::raw('NULL')]);

        DB::table('user_types')
            ->whereNull('name_ar')
            ->whereNotNull('description')
            ->update(['name_ar' => DB::raw('description')]);

        DB::table('user_types')
            ->whereNull('name_en')
            ->whereNotNull('name')
            ->update(['name_en' => DB::raw('name')]);

        DB::table('user_types')
            ->whereNull('name')
            ->whereNotNull('name_ar')
            ->update(['name' => DB::raw('name_ar')]);
    }

    public function down(): void
    {
        // Data fix - no rollback needed
    }
};
