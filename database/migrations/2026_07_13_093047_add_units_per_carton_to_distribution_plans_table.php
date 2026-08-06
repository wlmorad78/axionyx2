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
        Schema::table('distribution_plans', function (Blueprint $table) {
            $table->integer('units_per_carton')->default(50)->after('total_demand');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_plans', function (Blueprint $table) {
            $table->dropColumn('units_per_carton');
        });
    }
};
