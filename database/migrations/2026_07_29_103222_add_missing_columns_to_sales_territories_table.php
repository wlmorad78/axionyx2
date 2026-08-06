<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_territories', function (Blueprint $table) {
            $table->foreignId('governorate_id')->nullable()->after('name_en')->constrained('governorates');
            $table->foreignId('warehouse_id')->nullable()->after('governorate_id')->constrained('warehouses');
            $table->foreignId('treasury_id')->nullable()->after('warehouse_id')->constrained('treasuries');
        });
    }

    public function down(): void
    {
        Schema::table('sales_territories', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['treasury_id']);
            $table->dropColumn(['governorate_id', 'warehouse_id', 'treasury_id']);
        });
    }
};
