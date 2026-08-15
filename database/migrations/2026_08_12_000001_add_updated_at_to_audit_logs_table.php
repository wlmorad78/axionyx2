<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('audit_logs', 'updated_at')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }

        DB::table('audit_logs')
            ->whereNull('updated_at')
            ->update(['updated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'updated_at')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
