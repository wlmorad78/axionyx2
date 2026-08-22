<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void {
        Schema::table('vehicle_daily_expenses', function (Blueprint $table) {
            $table->decimal('km', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void {
        Schema::table('vehicle_daily_expenses', function (Blueprint $table) {
            $table->dropColumn('km');
        });
    }
};
