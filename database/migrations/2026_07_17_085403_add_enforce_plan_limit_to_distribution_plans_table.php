<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribution_plans', function (Blueprint $table) {
            $table->boolean('enforce_plan_limit')->default(false)->after('allocation_factor');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_plans', function (Blueprint $table) {
            $table->dropColumn('enforce_plan_limit');
        });
    }
};
