<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void {
        Schema::table('vehicle_daily_expenses', function (Blueprint $table) {
            $table->time('expense_time')->nullable()->after('expense_date');
        });

        Schema::table('vehicle_fuel_transactions', function (Blueprint $table) {
            $table->time('transaction_time')->nullable()->after('transaction_date');
        });
    }

    public function down(): void {
        Schema::table('vehicle_daily_expenses', function (Blueprint $table) {
            $table->dropColumn('expense_time');
        });

        Schema::table('vehicle_fuel_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_time');
        });
    }
};
