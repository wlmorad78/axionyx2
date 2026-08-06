<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->unique(['company_id', 'code']);
        });
        Schema::table('customer_classes', function (Blueprint $table) {
            $table->unique(['company_id', 'code']);
        });
        Schema::table('customer_types', function (Blueprint $table) {
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
        });
        Schema::table('customer_classes', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
        });
        Schema::table('customer_types', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
        });
    }
};
