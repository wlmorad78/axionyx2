<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('branch_id');
            $table->unsignedBigInteger('treasury_id')->nullable()->after('warehouse_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('treasury_id')->references('id')->on('treasuries')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['treasury_id']);
            $table->dropColumn(['branch_id', 'warehouse_id', 'treasury_id']);
        });
    }
};
