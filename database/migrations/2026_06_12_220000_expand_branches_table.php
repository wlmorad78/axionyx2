<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('code', 50)->change();
            $table->string('name_ar')->after('name_en')->nullable();
            $table->foreignId('country_id')->nullable()->after('name_en')->constrained('countries')->nullOnDelete();
            $table->foreignId('governorate_id')->nullable()->after('country_id')->constrained('governorates')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('governorate_id')->constrained('cities')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('city_id')->constrained('districts')->nullOnDelete();
            $table->string('address_line_1')->nullable()->after('area_id');
            $table->string('mobile', 50)->nullable()->after('phone');
            $table->unsignedBigInteger('manager_employee_id')->nullable()->after('email');
            $table->boolean('is_head_office')->default(false)->after('manager_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar', 'country_id', 'governorate_id', 'city_id', 'area_id',
                'address_line_1', 'mobile', 'manager_employee_id', 'is_head_office',
            ]);
        });
    }
};
