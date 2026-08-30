<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payment_methods', 'code')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->unique()->after('name');
            });
        }
        if (!Schema::hasColumn('payment_methods', 'requires_bank_account')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->boolean('requires_bank_account')->default(false)->after('code');
            });
        }

        $methods = [
            ['code' => 'cash', 'name' => 'نقدي', 'requires_bank_account' => false],
            ['code' => 'bank_transfer', 'name' => 'تحويل بنكي', 'requires_bank_account' => true],
            ['code' => 'customer_balance', 'name' => 'رصيد العميل', 'requires_bank_account' => false],
        ];

        foreach ($methods as $m) {
            DB::table('payment_methods')->updateOrInsert(
                ['code' => $m['code']],
                [
                    'name' => $m['name'],
                    'requires_bank_account' => $m['requires_bank_account'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('payment_methods')->whereIn('code', ['cash', 'bank_transfer', 'customer_balance'])->delete();
        if (Schema::hasColumn('payment_methods', 'requires_bank_account')) {
            Schema::table('payment_methods', fn (Blueprint $table) => $table->dropColumn('requires_bank_account'));
        }
        if (Schema::hasColumn('payment_methods', 'code')) {
            Schema::table('payment_methods', fn (Blueprint $table) => $table->dropColumn('code'));
        }
    }
};
