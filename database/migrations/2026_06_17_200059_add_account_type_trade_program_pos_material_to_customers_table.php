<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('account_type', 50)->nullable()->after('payment_term_days');
            $table->string('trade_program_type', 50)->nullable()->after('account_type');
            $table->string('pos_material')->nullable()->after('trade_program_type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'trade_program_type', 'pos_material']);
        });
    }
};
