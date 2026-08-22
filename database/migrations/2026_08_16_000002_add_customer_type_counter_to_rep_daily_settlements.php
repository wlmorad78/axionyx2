<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rep_daily_settlements', function (Blueprint $table) {
            $table->string('customer_type', 50)->nullable()->after('sales_rep_id');
            $table->string('counter', 50)->nullable()->after('customer_type');
            $table->string('new_counter_number', 50)->nullable()->after('counter');
            $table->text('return_notes')->nullable()->after('new_counter_number');
        });
    }

    public function down(): void
    {
        Schema::table('rep_daily_settlements', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'counter', 'new_counter_number', 'return_notes']);
        });
    }
};
