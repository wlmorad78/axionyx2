<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bank_accounts', 'branch_code')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->string('branch_code', 50)->nullable()->after('branch_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_accounts', 'branch_code')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropColumn('branch_code');
            });
        }
    }
};
