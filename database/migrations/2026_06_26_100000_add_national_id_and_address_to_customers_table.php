<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('national_id')->nullable()->after('tax_number');
            $table->boolean('has_whatsapp')->default(false)->after('mobile');
            $table->string('whatsapp_number')->nullable()->after('has_whatsapp');
            $table->foreignId('governorate_id')->nullable()->after('company_id')->constrained('governorates');
            $table->foreignId('city_id')->nullable()->after('governorate_id')->constrained('cities');
            $table->foreignId('area_id')->nullable()->after('city_id')->constrained('districts');
            $table->text('address_line')->nullable()->after('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'national_id', 'has_whatsapp', 'whatsapp_number',
                'governorate_id', 'city_id', 'area_id', 'address_line',
            ]);
        });
    }
};
