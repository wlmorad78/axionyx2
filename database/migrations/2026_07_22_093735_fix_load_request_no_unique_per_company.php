<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->dropUnique(['request_no']);
            $table->unique(['company_id', 'request_no']);
        });
    }

    public function down(): void
    {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'request_no']);
            $table->unique(['request_no']);
        });
    }
};
