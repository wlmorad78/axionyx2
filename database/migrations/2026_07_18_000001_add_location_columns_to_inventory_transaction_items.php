<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('inventory_transaction_items', function (Blueprint $table) {
            $table->string('from_location_type', 30)->nullable()->after('total_cost')
                ->comment('supplier, warehouse, rep, vehicle, customer');
            $table->unsignedBigInteger('from_location_id')->nullable()->after('from_location_type');
            $table->string('to_location_type', 30)->nullable()->after('from_location_id')
                ->comment('supplier, warehouse, rep, vehicle, customer');
            $table->unsignedBigInteger('to_location_id')->nullable()->after('to_location_type');
        });
    }

    public function down(): void {
        Schema::table('inventory_transaction_items', function (Blueprint $table) {
            $table->dropColumn(['from_location_type', 'from_location_id', 'to_location_type', 'to_location_id']);
        });
    }
};
