<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_modules', function (Blueprint $table) {
            $table->boolean('is_active')->after('sort_order')->default(true);
        });

        Schema::table('admin_screens', function (Blueprint $table) {
            $table->boolean('is_active')->after('sort_order')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('admin_modules', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('admin_screens', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
