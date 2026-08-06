<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('customer_visits')
            ->where('visit_status', 'missed')
            ->update(['visit_status' => 'visit']);
    }

    public function down(): void
    {
        DB::table('customer_visits')
            ->where('visit_status', 'visit')
            ->update(['visit_status' => 'missed']);
    }
};
