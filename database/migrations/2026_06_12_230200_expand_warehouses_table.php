<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('name_ar')->after('code')->nullable();
            $table->string('name_en')->after('name_ar')->nullable();
            $table->string('phone')->after('name_en')->nullable();
            $table->foreignId('manager_employee_id')->after('phone')->nullable();
            $table->text('notes')->after('manager_employee_id')->nullable();
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropForeign(['manager_employee_id']);
            $table->dropColumn(['name_ar', 'name_en', 'phone', 'manager_employee_id', 'notes']);
        });
    }
};
