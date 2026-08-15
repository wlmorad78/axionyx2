<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salesman_assignments', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('sales_territory_id')->constrained('warehouses')->nullOnDelete();
            $table->foreignId('treasury_id')->nullable()->after('warehouse_id')->constrained('treasuries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salesman_assignments', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['treasury_id']);
            $table->dropColumn(['warehouse_id', 'treasury_id']);
        });
    }
};
