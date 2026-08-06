<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('name_ar')->after('code')->nullable();
            $table->string('commercial_name_ar')->after('name_en')->nullable();
            $table->string('commercial_name_en')->after('commercial_name_ar')->nullable();
            $table->foreignId('currency_id')->nullable()->after('commercial_register')->constrained('currencies')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->after('currency_id')->constrained('countries')->nullOnDelete();
            $table->foreignId('governorate_id')->nullable()->after('country_id')->constrained('governorates')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('governorate_id')->constrained('cities')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('city_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('street_id')->nullable()->after('area_id')->constrained('streets')->nullOnDelete();
            $table->string('address_line_1')->nullable()->after('street_id');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('postal_code', 20)->nullable()->after('address_line_2');
            $table->string('website')->nullable()->after('email');
            $table->unsignedBigInteger('logo_attachment_id')->nullable()->after('website');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar', 'commercial_name_ar', 'commercial_name_en',
                'currency_id', 'country_id', 'governorate_id', 'city_id', 'area_id', 'street_id',
                'address_line_1', 'address_line_2', 'postal_code',
                'website', 'logo_attachment_id',
                'created_by', 'updated_by', 'deleted_by',
            ]);
        });
    }
};
