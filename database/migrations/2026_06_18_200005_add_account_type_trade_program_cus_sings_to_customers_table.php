<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_account_type_id')->nullable()->after('customer_type_id')->constrained('customer_account_types');
            $table->foreignId('trade_program_type_id')->nullable()->after('customer_account_type_id')->constrained('trade_program_types');
            $table->boolean('cus_sings')->default(false)->after('trade_program_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_account_type_id']);
            $table->dropForeign(['trade_program_type_id']);
            $table->dropColumn(['customer_account_type_id', 'trade_program_type_id', 'cus_sings']);
        });
    }
};
