<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('collections', 'payer_customer_id')) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS sqlite_autoindex_collections_1');
            }
            Schema::table('collections', function (Blueprint $table) {
                $table->foreignId('payer_customer_id')->nullable()->after('customer_id')->constrained('customers')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('bank_accounts', 'branch_id')) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS sqlite_autoindex_bank_accounts_1');
            }
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['payer_customer_id']);
            $table->dropColumn('payer_customer_id');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
