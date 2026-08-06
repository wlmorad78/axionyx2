<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_types', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->string('name_ar')->nullable()->after('code');
            $table->string('name_en')->nullable()->after('name_ar');
        });
    }

    public function down(): void
    {
        Schema::table('user_types', function (Blueprint $table) {
            $table->dropColumn(['code', 'name_ar', 'name_en']);
        });
    }
};
