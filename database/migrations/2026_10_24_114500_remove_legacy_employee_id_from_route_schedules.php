<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('route_schedules', 'employee_id')) {
            return;
        }

        Schema::table('route_schedules', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('route_schedules', 'employee_id')) {
            return;
        }

        Schema::table('route_schedules', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
        });
    }
};
