<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS customer_groups_code_unique');
        DB::statement('DROP INDEX IF EXISTS customer_classes_code_unique');
        DB::statement('DROP INDEX IF EXISTS customer_types_code_unique');
    }

    public function down(): void
    {
        //
    }
};
