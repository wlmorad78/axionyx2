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
        Schema::table('opening_balance_documents', function (Blueprint $table) {
            $table->string('balance_type', 20)->default('accounts')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('opening_balance_documents', function (Blueprint $table) {
            $table->dropColumn('balance_type');
        });
    }
};
