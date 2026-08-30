<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('collections', 'payer_customer_id')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->foreignId('payer_customer_id')->nullable()->after('customer_id');
            });
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table('collections', function (Blueprint $table) {
                    $table->foreign('payer_customer_id')->references('id')->on('customers')->nullOnDelete();
                });
            }
        }

        if (!Schema::hasColumn('bank_accounts', 'branch_id')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id');
            });
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table('bank_accounts', function (Blueprint $table) {
                    $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['payer_customer_id']);
            }
            $table->dropColumn('payer_customer_id');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['branch_id']);
            }
            $table->dropColumn('branch_id');
        });
    }
};
