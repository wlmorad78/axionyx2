<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            ['code' => 'SI', 'name' => 'فواتير المبيعات', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PI', 'name' => 'فواتير المشتريات', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'JV', 'name' => 'قيود يومية', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'MJ', 'name' => 'قيود يدوية', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($types as $type) {
            DB::table('journal_entry_types')->updateOrInsert(
                ['code' => $type['code']],
                $type
            );
        }
    }

    public function down(): void
    {
        DB::table('journal_entry_types')->whereIn('code', ['SI', 'PI'])->delete();
    }
};
