<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_territory_id')->nullable()->after('commercial_register')->comment('المنطقة البيعية');
            $table->unsignedBigInteger('route_line_id')->nullable()->after('sales_territory_id')->comment('خط السير');
            $table->index('sales_territory_id');
            $table->index('route_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['sales_territory_id']);
            $table->dropIndex(['route_line_id']);
            $table->dropColumn(['sales_territory_id', 'route_line_id']);
        });
    }
};
