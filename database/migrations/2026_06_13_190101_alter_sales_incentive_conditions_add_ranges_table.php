<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sales_incentive_conditions', function (Blueprint $table) {
            $table->decimal('from_qty', 12, 2)->nullable()->after('condition_value');
            $table->decimal('to_qty', 12, 2)->nullable()->after('from_qty');
            $table->decimal('from_amount', 12, 2)->nullable()->after('to_qty');
            $table->decimal('to_amount', 12, 2)->nullable()->after('from_amount');
        });
    }
    public function down(): void {
        Schema::table('sales_incentive_conditions', function (Blueprint $table) {
            $table->dropColumn(['from_qty', 'to_qty', 'from_amount', 'to_amount']);
        });
    }
};
