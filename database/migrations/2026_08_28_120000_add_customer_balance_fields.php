<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique()->after('name');
            $table->boolean('requires_bank_account')->default(false)->after('is_active');
        });

        DB::table('payment_methods')->updateOrInsert(
            ['code' => 'bank_transfer'],
            ['name' => 'تحويل بنكي', 'is_active' => true, 'requires_bank_account' => true, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'requires_bank_account']);
        });
    }
};
